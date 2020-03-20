<div class="wrap s_booking">
    <div class="s_alert">
        <?php if ($error): ?>
            <?= $error; ?>
        <?php endif; ?>
    </div>
    <form action="" method="post">
        <?php wp_nonce_field('s_booking_add', 's_booking_add'); ?>
        <ul class="addNewHours">
            <li>
                <label for="bslc_day"><?= _x('Day', 'booking-special-calendar'); ?></label>
                <input type="date" name="bslc_day" id="bslc_day">
            </li>
            <li>
                <label for="bslc_from"><?= _x('From', 'booking-special-calendar'); ?></label>
                <select name="bslc_from" id="bslc_from">
                    <?php for ($x = 0; $x <= 24; $x++): ?>
                        <option value="<?= $x; ?>">
                            <?= strlen($x) < 2 ? '0' . $x : $x; ?>:00:00
                        </option>
                    <?php endfor; ?>
                </select>
            </li>
            <li>
                <label for="bslc_to"><?= _x('To', 'booking-special-calendar'); ?></label>
                <select name="bslc_to" id="bslc_to">
                    <?php for ($x = 0; $x <= 24; $x++): ?>
                        <option value="<?= $x; ?>">
                            <?= strlen($x) < 2 ? '0' . $x : $x; ?>:00:00
                        </option>
                    <?php endfor; ?>
                </select>
            </li>
            <li>
                <label for="bslc_jumps"><?= _x('Time for single meet', 'booking-special-calendar'); ?></label>
                <select name="bslc_jumps" id="bslc_jumps">
                    <option value="30"><?= _x('30 minutes', 'booking-special-calendar'); ?></option>
                    <option value="60"><?= _x('60 minutes', 'booking-special-calendar'); ?></option>
                </select>
            </li>
            <li>
                <button type="submit" name="s_booking_submit"
                        class="button-primary"><?= _x('Open hours', 'booking-special-calendar'); ?></button>
            </li>
        </ul>
    </form>
</div>
