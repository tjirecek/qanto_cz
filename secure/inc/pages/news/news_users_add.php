<?php
declare(strict_types=1);

// žádné mysqli
$name  = trim((string)($_POST['name']  ?? ''));
$email = trim((string)($_POST['email'] ?? ''));

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="name" class="form-label">Jméno, popis</label>
                    <input type="text"
                           name="name"
                           id="name"
                           class="form-control"
                           value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>">
                </div>

                <div class="col-md-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="text"
                           name="email"
                           id="email"
                           class="form-control"
                           value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
                </div>

                <div class="col-md-4">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        Vložit uživatele novinek
                    </button>
                </div>
            </div>
        </form>

    <?php elseif ($add === 1): ?>

        <?php news_users_add($name, $email); ?>

    <?php endif; ?>
</div>