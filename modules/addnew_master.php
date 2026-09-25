<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Add New';
$pageDescription = 'Add new master data.';

$message = '';
$error = '';
$name = '';
$master_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $master_type = trim($_POST['master_type'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if ($master_type === '') {

        $error = 'Please select what you want to add.';

    } elseif ($name === '') {

        $error = 'Please enter a name.';

    } elseif ($master_type === 'industry') {

        // Check duplicate
        $existing = fetch_one(
            "SELECT id
             FROM industries
             WHERE industry_name = ?
             LIMIT 1",
            [$name]
        );

        if ($existing) {

            $error = 'This industry already exists.';

        } else {

            $saved = execute_query(
                "INSERT INTO industries (industry_name, status)
                 VALUES (?, 1)",
                [$name]
            );

            if ($saved) {

                $message = 'Industry added successfully.';
                $name = '';

            } else {

                $error = 'Unable to save industry.';
            }
        }

    } else {

        $error = 'Invalid master type selected.';
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="panel">

        <div class="panel-header">
            <h3>Add New</h3>
        </div>

        <?php if ($message): ?>

            <div class="alert alert-success">
                <?= esc($message) ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= esc($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label for="master_type">
                        What do you want to add?
                    </label>

                    <select
                        name="master_type"
                        id="master_type"
                        required
                    >

                        <option value="">
                            Select
                        </option>

                        <option
                            value="industry"
                            <?= ($master_type === 'industry') ? 'selected' : '' ?>
                        >
                            Industry
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="name">
                        New Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        placeholder="Enter new industry name"
                        value="<?= esc($name) ?>"
                        required
                    >

                </div>

            </div>


            <div style="margin-top: 20px;">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Save
                </button>

            </div>

        </form>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>