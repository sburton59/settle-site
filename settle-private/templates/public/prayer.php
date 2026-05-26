<?php
/** @var array $errors    Field-keyed validation errors (or []) */
/** @var array $data      Posted form values to redisplay (or blanks) */
/** @var bool  $success   True when the submission was accepted */

$nameVal  = htmlspecialchars((string)($data['submitter_name']  ?? ''), ENT_QUOTES);
$emailVal = htmlspecialchars((string)($data['submitter_email'] ?? ''), ENT_QUOTES);
$bodyVal  = htmlspecialchars((string)($data['request_text']    ?? ''), ENT_QUOTES);
$priv     = !empty($data['is_private']);
?>

<div style="max-width:640px; margin:2em auto; padding:1em;">
    <h1>Submit a Prayer Request</h1>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:1.5em;
                    border-radius:4px; margin-bottom:1em;">
            <p style="margin:0;">
                <strong>Thank you.</strong> Your prayer request has been received,
                and our prayer team will lift it up.
            </p>
        </div>

        <p>
            <a href="/">Return to the homepage</a> ·
            <a href="/prayer">Submit another request</a>
        </p>

    <?php else: ?>
        <p style="color:#555; line-height:1.5;">
            We would be honored to pray with and for you. Use this form to share
            a prayer need — whether for yourself, someone you love, or our community.
            All fields except the request itself are optional.
        </p>

        <form method="post" action="/prayer" novalidate
              style="background:#fff; padding:1.5em; border-radius:4px;
                     box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-top:1em;">
            <?= \Settle\Csrf::field() ?>

            <!--
              Honeypot field. Real browsers don't see it, but bots that
              auto-fill every input will. Server treats any non-empty value
              as a silent drop. Position-absolute keeps it out of layout,
              tabindex=-1 keeps it out of tab order, autocomplete=off keeps
              browsers from helpfully prefilling it.
            -->
            <div style="position:absolute; left:-9999px; top:auto;
                        width:1px; height:1px; overflow:hidden;"
                 aria-hidden="true">
                <label for="website">Website (leave blank)</label>
                <input type="text" id="website" name="website" value=""
                       tabindex="-1" autocomplete="off">
            </div>

            <!-- Name -->
            <div style="margin-bottom:1em;">
                <label for="submitter_name" style="display:block; font-weight:500; margin-bottom:0.3em;">
                    Your name <span class="muted" style="font-weight:normal;">(optional)</span>
                </label>
                <input type="text" id="submitter_name" name="submitter_name"
                       value="<?= $nameVal ?>"
                       maxlength="150"
                       style="width:100%; padding:0.5em; box-sizing:border-box;
                              border:1px solid #ccc; border-radius:3px;">
                <?php if (!empty($errors['submitter_name'])): ?>
                    <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
                        <?= htmlspecialchars($errors['submitter_name'], ENT_QUOTES) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div style="margin-bottom:1em;">
                <label for="submitter_email" style="display:block; font-weight:500; margin-bottom:0.3em;">
                    Your email <span class="muted" style="font-weight:normal;">(optional)</span>
                </label>
                <input type="email" id="submitter_email" name="submitter_email"
                       value="<?= $emailVal ?>"
                       maxlength="190"
                       style="width:100%; padding:0.5em; box-sizing:border-box;
                              border:1px solid #ccc; border-radius:3px;">
                <?php if (!empty($errors['submitter_email'])): ?>
                    <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
                        <?= htmlspecialchars($errors['submitter_email'], ENT_QUOTES) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Request body -->
            <div style="margin-bottom:1em;">
                <label for="request_text" style="display:block; font-weight:500; margin-bottom:0.3em;">
                    Prayer request <span style="color:var(--error);">*</span>
                </label>
                <textarea id="request_text" name="request_text" rows="8"
                          maxlength="5000" required
                          style="width:100%; padding:0.5em; box-sizing:border-box;
                                 border:1px solid #ccc; border-radius:3px;
                                 font-family:inherit; font-size:1em; resize:vertical;"
                          ><?= $bodyVal ?></textarea>
                <?php if (!empty($errors['request_text'])): ?>
                    <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
                        <?= htmlspecialchars($errors['request_text'], ENT_QUOTES) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Private flag -->
            <div style="margin-bottom:1.5em;
                        padding:0.8em; background:#f8f8f8; border-radius:3px;">
                <label style="display:flex; gap:0.5em; align-items:flex-start; cursor:pointer;">
                    <input type="checkbox" name="is_private" value="1"
                           style="margin-top:0.25em;"
                           <?= $priv ? 'checked' : '' ?>>
                    <span>
                        <strong>Keep this request private.</strong>
                        <span class="muted" style="display:block; font-size:0.9em; margin-top:0.25em;">
                            Private requests will only be seen by our pastoral staff.
                        </span>
                    </span>
                </label>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary"
                    style="padding:0.7em 1.5em; font-size:1em; cursor:pointer;">
                Submit prayer request
            </button>
        </form>
    <?php endif; ?>
</div>
