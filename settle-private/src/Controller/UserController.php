<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Model\User;

/**
 * Admin User Management (roadmap #5).
 *
 * Staff logins for the admin panel: list, create, edit, activate/deactivate,
 * and delete. Replaces the hand-run seed_authors.sql workflow for ongoing
 * management (the seed file remains useful for first-boot bulk creation).
 *
 * Access: ADMIN-ONLY. Like Settings, this is core admin rather than an
 * optional feature, so there is no Features flag — just the route-level
 * role gate AND a defense-in-depth Auth::hasRole('admin') check here.
 *
 * Lockout rails (enforced in-code — the router can express "admin or higher"
 * but not these record-relative rules):
 *   1. You cannot change your OWN role.
 *   2. You cannot deactivate or delete your OWN account.
 *   3. No action may leave the site with zero active administrators.
 * In practice an acting admin is always one active admin, so (3) is mainly
 * reachable via self-action, which (1)/(2) already block; it is enforced
 * anyway to cover odd states (e.g. an account disabled directly in the DB).
 *
 * Passwords: create requires an initial password (min 12); edit can set a
 * new one (leave blank to keep the current). Hashed with Argon2id; never
 * read back, never logged. (#6b will add self-service reset by email.)
 *
 * Deletion: several tables reference users(id) ON DELETE RESTRICT, so a user
 * who has authored content cannot be hard-deleted. destroy() catches the
 * resulting \PDOException and steers the admin to deactivation instead.
 *
 * See PROJECT_HANDOFF.md §3.4 (roles), §3.5 (security baseline), §9
 * (conventions), §13.9 (View::render key-collision — values key avoided).
 */
final class UserController extends BaseController
{
    private const ROLES = ['author', 'editor', 'admin'];
    private const MIN_PASSWORD = 12;
    private const MAX_PASSWORD = 200;

    // -----------------------------------------------------------------
    // List
    // -----------------------------------------------------------------

    public function index(): void
    {
        if (!$this->guardAdmin()) return;
        $this->render('admin/users/index', [
            'users' => User::all(),
        ]);
    }

    // -----------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------

    public function create(): void
    {
        if (!$this->guardAdmin()) return;
        $this->render('admin/users/edit', [
            'person' => User::blank(),
            'isNew'  => true,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        if (!$this->guardAdmin()) return;

        $data   = $this->collectFormData();
        $errors = $this->validate($data, null, true);

        if ($errors) {
            $this->render('admin/users/edit', [
                'person' => array_merge(User::blank(), $data),
                'isNew'  => true,
                'errors' => $errors,
            ]);
            return;
        }

        $id = User::create([
            'username'      => $data['username'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID),
            'display_name'  => $data['display_name'],
            'role'          => $data['role'],
            'is_active'     => $data['is_active'],
        ]);

        AuditLog::record('user.create', 'user', $id, [
            'username' => $data['username'],
            'role'     => $data['role'],
        ]);

        $this->flash('success', 'User created.');
        $this->redirect('/admin/users');
    }

    // -----------------------------------------------------------------
    // Edit / update
    // -----------------------------------------------------------------

    public function edit(array $params): void
    {
        if (!$this->guardAdmin()) return;

        $person = User::find((int)$params['id']);
        if (!$person) { http_response_code(404); echo 'User not found.'; return; }

        $this->render('admin/users/edit', [
            'person' => $person,
            'isNew'  => false,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        if (!$this->guardAdmin()) return;

        $id     = (int)$params['id'];
        $person = User::find($id);
        if (!$person) { http_response_code(404); echo 'User not found.'; return; }

        $isSelf = ($id === $this->meId());
        $data   = $this->collectFormData();

        // Rails 1 & 2: an admin can never change their own role or
        // deactivate themselves. Force those fields back to current state
        // regardless of what was posted (the form also disables them).
        if ($isSelf) {
            $data['role']      = $person['role'];
            $data['is_active'] = 1;
        }

        $errors = $this->validate($data, $id, false);

        // Rail 3: never strand the site without an active admin.
        if (!$errors && $this->wouldLeaveNoActiveAdmin($person, false, $data['role'], $data['is_active'])) {
            $errors['is_active'] = 'This would leave the site with no active administrator. Promote or activate another admin first.';
        }

        if ($errors) {
            $this->render('admin/users/edit', [
                'person' => array_merge($person, $data),
                'isNew'  => false,
                'errors' => $errors,
            ]);
            return;
        }

        // Work out what changed (for targeted audit verbs).
        $roleChanged   = ($person['role'] !== $data['role']);
        $activeChanged = ((int)$person['is_active'] !== (int)$data['is_active']);
        $otherChanged  = [];
        foreach (['username', 'email', 'display_name'] as $k) {
            if ((string)$person[$k] !== (string)$data[$k]) {
                $otherChanged[] = $k;
            }
        }

        User::update($id, [
            'username'     => $data['username'],
            'email'        => $data['email'],
            'display_name' => $data['display_name'],
            'role'         => $data['role'],
            'is_active'    => $data['is_active'],
        ]);

        if ($data['password'] !== '') {
            User::updatePasswordHash($id, password_hash($data['password'], PASSWORD_ARGON2ID));
            AuditLog::record('user.password_reset', 'user', $id);
        }

        if ($roleChanged) {
            AuditLog::record('user.role_change', 'user', $id, [
                'from' => $person['role'],
                'to'   => $data['role'],
            ]);
        }
        if ($activeChanged) {
            AuditLog::record(
                $data['is_active'] ? 'user.activate' : 'user.deactivate',
                'user',
                $id
            );
        }
        if ($otherChanged) {
            AuditLog::record('user.update', 'user', $id, ['changed' => $otherChanged]);
        }

        $this->flash('success', 'User saved.');
        $this->redirect('/admin/users');
    }

    // -----------------------------------------------------------------
    // Activate / deactivate (list quick action)
    // -----------------------------------------------------------------

    public function toggleActive(array $params): void
    {
        if (!$this->guardAdmin()) return;

        $id     = (int)$params['id'];
        $person = User::find($id);
        if (!$person) { http_response_code(404); echo 'User not found.'; return; }

        if ($id === $this->meId()) {
            $this->flash('error', 'You cannot deactivate your own account.');
            $this->redirect('/admin/users');
            return;
        }

        $newActive = ((int)$person['is_active'] === 1) ? 0 : 1;

        if ($newActive === 0
            && $this->wouldLeaveNoActiveAdmin($person, false, $person['role'], 0)) {
            $this->flash('error', 'That is the last active administrator — activate or promote another admin first.');
            $this->redirect('/admin/users');
            return;
        }

        User::setActive($id, (bool)$newActive);
        AuditLog::record($newActive ? 'user.activate' : 'user.deactivate', 'user', $id);

        $this->flash('success', $newActive ? 'User activated.' : 'User deactivated.');
        $this->redirect('/admin/users');
    }

    // -----------------------------------------------------------------
    // Delete
    // -----------------------------------------------------------------

    public function destroy(array $params): void
    {
        if (!$this->guardAdmin()) return;

        $id     = (int)$params['id'];
        $person = User::find($id);
        if (!$person) { http_response_code(404); echo 'User not found.'; return; }

        if ($id === $this->meId()) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
            return;
        }

        if ($this->wouldLeaveNoActiveAdmin($person, true)) {
            $this->flash('error', 'That is the last active administrator — promote or activate another admin first.');
            $this->redirect('/admin/users');
            return;
        }

        try {
            User::delete($id);
            AuditLog::record('user.delete', 'user', $id, ['username' => $person['username']]);
            $this->flash('success', 'User deleted.');
        } catch (\PDOException $e) {
            // ON DELETE RESTRICT — the user owns posts/pages/media/overrides.
            $this->flash('error',
                'This user has authored content (posts, pages, photos, or calendar notes) and cannot be deleted. Deactivate them instead to remove their access.');
        }

        $this->redirect('/admin/users');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function guardAdmin(): bool
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return false;
        }
        return true;
    }

    private function meId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Would applying this change leave zero active admins? Computes the
     * post-action active-admin count from the current count.
     */
    private function wouldLeaveNoActiveAdmin(
        array $current,
        bool $deleting,
        ?string $newRole = null,
        ?int $newActive = null
    ): bool {
        $count = User::countActiveAdmins();

        $wasActiveAdmin = ($current['role'] === 'admin' && (int)$current['is_active'] === 1);

        if ($deleting) {
            $willBeActiveAdmin = false;
        } else {
            $role              = $newRole ?? $current['role'];
            $active            = $newActive ?? (int)$current['is_active'];
            $willBeActiveAdmin = ($role === 'admin' && (int)$active === 1);
        }

        if ($wasActiveAdmin && !$willBeActiveAdmin) { $count--; }
        // Promotion to active admin only raises the count; never strands.

        return $count < 1;
    }

    private function collectFormData(): array
    {
        $role = (string)$this->input('role', 'author');
        if (!in_array($role, self::ROLES, true)) {
            $role = 'author';
        }

        return [
            'username'         => trim((string)$this->input('username', '')),
            'email'            => trim((string)$this->input('email', '')),
            'display_name'     => trim((string)$this->input('display_name', '')),
            'role'             => $role,
            'is_active'        => $this->input('is_active') ? 1 : 0,
            'password'         => (string)$this->input('password', ''),
            'password_confirm' => (string)$this->input('password_confirm', ''),
        ];
    }

    /**
     * @param int|null $id  null on create; the row id on edit (for unique-excluding-self)
     */
    private function validate(array $data, ?int $id, bool $isNew): array
    {
        $errors = [];

        // Username
        if ($data['username'] === '') {
            $errors['username'] = 'A username is required.';
        } elseif (mb_strlen($data['username']) > 50) {
            $errors['username'] = 'Username must be 50 characters or fewer.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $data['username'])) {
            $errors['username'] = 'Username may use letters, numbers, dots, hyphens and underscores (3–50 characters).';
        } elseif (User::usernameExists($data['username'], $id)) {
            $errors['username'] = 'That username is already taken.';
        }

        // Email (NOT NULL in schema)
        if ($data['email'] === '') {
            $errors['email'] = 'An email address is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That doesn\'t look like a valid email address.';
        } elseif (mb_strlen($data['email']) > 190) {
            $errors['email'] = 'Email must be 190 characters or fewer.';
        } elseif (User::emailExists($data['email'], $id)) {
            $errors['email'] = 'That email address is already in use.';
        }

        // Display name
        if ($data['display_name'] === '') {
            $errors['display_name'] = 'A display name is required.';
        } elseif (mb_strlen($data['display_name']) > 100) {
            $errors['display_name'] = 'Display name must be 100 characters or fewer.';
        }

        // Role
        if (!in_array($data['role'], self::ROLES, true)) {
            $errors['role'] = 'Please choose a valid role.';
        }

        // Password — required on create; optional on edit (blank = unchanged).
        $wantsPassword = $isNew
            || $data['password'] !== ''
            || $data['password_confirm'] !== '';

        if ($wantsPassword) {
            if ($data['password'] === '') {
                // Explicit, unconditional guard: a new user MUST have a
                // password, and a password change must not blank it. (The
                // length check below also catches '', but this gives a
                // clear message and closes the path even if the numeric
                // comparison is ever weakened.)
                $errors['password'] = $isNew
                    ? 'A password is required for a new user.'
                    : 'Enter a new password, or leave both fields blank to keep the current one.';
            } elseif (mb_strlen($data['password']) < self::MIN_PASSWORD) {
                $errors['password'] = 'Password must be at least ' . self::MIN_PASSWORD . ' characters.';
            } elseif (mb_strlen($data['password']) > self::MAX_PASSWORD) {
                $errors['password'] = 'Password must be ' . self::MAX_PASSWORD . ' characters or fewer.';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password'] = 'The two passwords don\'t match.';
            }
        }

        return $errors;
    }
}
