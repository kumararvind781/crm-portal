<?php

require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Communications';
$pageDescription = 'Manage client communications.';


/*----------------------------------------------------
DELETE
-----------------------------------------------------*/

if(isset($_GET['delete']) && is_admin()){

    $row = fetch_one(
        "SELECT attachment FROM communications WHERE id=?",
        [(int)$_GET['delete']]
    );

    delete_file_if_exists($row['attachment'] ?? null);

    execute_query(
        "DELETE FROM communications WHERE id=?",
        [(int)$_GET['delete']]
    );

    redirect_path('modules/communications.php');

}


/*----------------------------------------------------
EDIT
-----------------------------------------------------*/

$editId = (int)($_GET['edit'] ?? 0);

$editItem = null;

if($editId){

    $editItem = fetch_one(

        "SELECT * FROM communications WHERE id=?",

        [$editId]

    );

}


/*----------------------------------------------------
SAVE
-----------------------------------------------------*/

if($_SERVER['REQUEST_METHOD']=='POST'){

    $companyId = (int)$_POST['company_id'];

    $contactId = (int)$_POST['contact_id'];

    $communicationBy = trim($_POST['communication_by']);

    $communicationType = trim($_POST['communication_type']);

    $subject = trim($_POST['subject']);

    $communicationDate = $_POST['communication_date'];

    $communication = trim($_POST['communication']);

    $nextFollowup = !empty($_POST['next_followup'])
        ? $_POST['next_followup']
        : null;


    /*-----------------------------
    Attachment Upload
    -----------------------------*/

    $attachment = $editItem['attachment'] ?? null;

    if(
        !empty($_FILES['attachment']['name'])
        &&
        $_FILES['attachment']['error']==0
    ){

        $attachment = upload_file(
            $_FILES['attachment'],
            'uploads/communications'
        );

    }


    if(!empty($_POST['id'])){

        execute_query(

        "UPDATE communications
        SET

            company_id=?,

            contact_id=?,

            communication_by=?,

            communication_type=?,

            subject=?,

            communication_date=?,

            communication=?,

            attachment=?,

            next_followup=?

        WHERE id=?",

        [

            $companyId,

            $contactId,

            $communicationBy,

            $communicationType,

            $subject,

            $communicationDate,

            $communication,

            $attachment,

            $nextFollowup,

            (int)$_POST['id']

        ]);

    }else{

        execute_query(

        "INSERT INTO communications(

            company_id,

            contact_id,

            communication_by,

            communication_type,

            subject,

            communication_date,

            communication,

            attachment,

            next_followup,

            created_by

        )

        VALUES(

            ?,?,?,?,?,?,?,?,?,?

        )",

        [

            $companyId,

            $contactId,

            $communicationBy,

            $communicationType,

            $subject,

            $communicationDate,

            $communication,

            $attachment,

            $nextFollowup,

            $_SESSION['user']['id']

        ]);

    }

    redirect_path('modules/communications.php');

}


/*----------------------------------------------------
Company List
-----------------------------------------------------*/

$companies = fetch_all(

"

SELECT

id,

company_name

FROM companies

ORDER BY company_name

"

);


/*----------------------------------------------------
Client List
-----------------------------------------------------*/

$clients = fetch_all(

"

SELECT

c.id,

c.first_name,

c.last_name,

c.company_id,

co.company_name

FROM clients c

LEFT JOIN companies co

ON co.id=c.company_id

ORDER BY c.first_name

"

);


/*----------------------------------------------------
Communication List
-----------------------------------------------------*/

$communications = fetch_all(

"

SELECT

cm.*,

co.company_name,

CONCAT(c.first_name,' ',c.last_name) AS client_name,

u.name AS created_name

FROM communications cm

LEFT JOIN companies co

ON co.id=cm.company_id

LEFT JOIN clients c

ON c.id=cm.contact_id

LEFT JOIN users u

ON u.id=cm.created_by

ORDER BY communication_date DESC

"

);


include __DIR__.'/../includes/header.php';

include __DIR__.'/../includes/sidebar.php';

?>

<main class="main-content">

<?php include __DIR__.'/../includes/topbar.php'; ?>

<section class="crud-layout">

<article class="panel form-panel">

    <div class="panel-header">
        <h3>
            <?= $editItem ? 'Edit Communication' : 'Add Communication' ?>
        </h3>
    </div>

    <form method="post"
          enctype="multipart/form-data"
          class="form-grid">

        <input type="hidden"
               name="id"
               value="<?= esc($editItem['id'] ?? '') ?>">

        <!-- Company -->

        <div>

            <label>Company <span class="required">*</span></label>

            <select
                name="company_id"
                id="company_id"
                required>

                <option value="">Select Company</option>

                <?php foreach($companies as $company): ?>

                    <option
                        value="<?= $company['id'] ?>"
                        <?= (($editItem['company_id'] ?? '')==$company['id']) ? 'selected':'' ?>>

                        <?= esc($company['company_name']) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <!-- Client -->

        <div>

            <label>Client <span class="required">*</span></label>

            <select
                name="contact_id"
                id="contact_id"
                required>

                <option value="">Select Client</option>

                <?php foreach($clients as $client): ?>

                    <option

                        value="<?= $client['id'] ?>"

                        data-company="<?= $client['company_id'] ?>"

                        <?= (($editItem['contact_id'] ?? '')==$client['id']) ? 'selected':'' ?>>

                        <?= esc(
                            $client['first_name'].' '.
                            $client['last_name']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <!-- Communication By -->

        <div>

            <label>Communication By</label>

            <select
                name="communication_by"
                required>

                <option value="Unire"
                <?= (($editItem['communication_by'] ?? '')=='Unire')?'selected':'' ?>>
                    Unire
                </option>

                <option value="Client"
                <?= (($editItem['communication_by'] ?? '')=='Client')?'selected':'' ?>>
                    Client
                </option>

            </select>

        </div>

        <!-- Communication Type -->

        <div>

            <label>Communication Type</label>

            <select
                name="communication_type"
                required>

                <?php

                $types=[

                    'Call',

                    'Email',

                    'WhatsApp',

                    'SMS',

                    'Meeting',

                    'Visit',

                    'Video Call',

                    'LinkedIn',

                    'Other'

                ];

                foreach($types as $type):

                ?>

                <option
                    value="<?= $type ?>"
                    <?= (($editItem['communication_type'] ?? '')==$type)?'selected':'' ?>>

                    <?= $type ?>

                </option>

                <?php endforeach; ?>

            </select>

        </div>

        <!-- Subject -->

        <div class="full">

            <label>Subject</label>

            <input
                type="text"
                name="subject"
                maxlength="255"
                value="<?= esc($editItem['subject'] ?? '') ?>"
                placeholder="Enter communication subject">

        </div>

        <!-- Date -->

        <div>

            <label>Date & Time</label>

            <input

                type="datetime-local"

                name="communication_date"

                value="<?= !empty($editItem['communication_date'])
                ? date('Y-m-d\TH:i',strtotime($editItem['communication_date']))
                : date('Y-m-d\TH:i') ?>"

                required>

        </div>

        <!-- Next Follow-up -->

        <div>

            <label>Next Follow-up</label>

            <input

                type="datetime-local"

                name="next_followup"

                value="<?= !empty($editItem['next_followup'])
                ? date('Y-m-d\TH:i',strtotime($editItem['next_followup']))
                : '' ?>">

        </div>

        <!-- Communication -->

        <div class="full">

            <label>Communication Details</label>

            <textarea

                name="communication"

                rows="6"

                placeholder="Write complete discussion here..."

                required><?= esc($editItem['communication'] ?? '') ?></textarea>

        </div>

        <!-- Attachment -->

        <div class="full">

            <label>Attachment</label>

            <input
                type="file"
                name="attachment">

            <?php if(!empty($editItem['attachment'])): ?>

                <br><br>

                <a

                    href="<?= BASE_URL.esc($editItem['attachment']) ?>"

                    target="_blank"

                    class="btn-link">

                    View Current Attachment

                </a>

            <?php endif; ?>

        </div>

        <!-- Button -->

        <div class="full">

            <button
                class="btn btn-primary"
                type="submit">

                <?= $editItem ? 'Update Communication' : 'Save Communication' ?>

            </button>

            <?php if($editItem): ?>

                <a

                    href="<?= BASE_URL ?>modules/communications.php"

                    class="btn btn-secondary">

                    Cancel

                </a>

            <?php endif; ?>

        </div>

    </form>

</article>

<article class="panel">

    <div class="panel-header">

        <h3>Communication History</h3>

    </div>

    <div class="table-wrap">

        <table>

            <thead>

            <tr>

                <th>Date</th>

                <th>Company</th>

                <th>Client</th>

                <th>By</th>

                <th>Type</th>

                <th>Subject</th>

                <th>Communication</th>

                <th>Attachment</th>

                <th>Next Follow-up</th>

                <th>Created By</th>

                <th width="180">Action</th>

            </tr>

            </thead>

            <tbody>

            <?php if(empty($communications)): ?>

                <tr>

                    <td colspan="11" class="text-center">

                        No communications found.

                    </td>

                </tr>

            <?php else: ?>

                <?php foreach($communications as $row): ?>

                <tr>

                    <td>

                        <?= date('d M Y',strtotime($row['communication_date'])) ?>

                        <br>

                        <small>

                            <?= date('h:i A',strtotime($row['communication_date'])) ?>

                        </small>

                    </td>

                    <td>

                        <?= esc($row['company_name']) ?>

                    </td>

                    <td>

                        <strong>

                            <?= esc($row['client_name']) ?>

                        </strong>

                    </td>

                    <td>

                        <?php if($row['communication_by']=='Client'): ?>

                            <span class="badge badge-danger">

                                Client

                            </span>

                        <?php else: ?>

                            <span class="badge badge-success">

                                Unire

                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <?= esc($row['communication_type']) ?>

                    </td>

                    <td>

                        <strong>

                            <?= esc($row['subject']) ?>

                        </strong>

                    </td>

                    <td style="max-width:300px;">

                        <?= nl2br(esc($row['communication'])) ?>

                    </td>

                    <td>

                        <?php if(!empty($row['attachment'])): ?>

                            <a

                            href="<?= BASE_URL.esc($row['attachment']) ?>"

                            target="_blank"

                            class="btn-link">

                            View

                            </a>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php

                        if(!empty($row['next_followup'])){

                            echo date(
                                'd M Y h:i A',
                                strtotime($row['next_followup'])
                            );
                        }else{

                            echo '-';

                        }

                        ?>

                    </td>

                    <td>

                        <?= esc($row['created_name']) ?>

                    </td>

                    <td class="action-cell">

                        <a

                        class="table-action"

                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= $row['contact_id'] ?>">

                        View Client

                        </a>

                        <a

                        class="table-action"

                        href="<?= BASE_URL ?>modules/communications.php?edit=<?= $row['id'] ?>">

                        Edit

                        </a>

                        <?php if(is_admin()): ?>

                        <a

                        class="table-action delete"

                        href="<?= BASE_URL ?>modules/communications.php?delete=<?= $row['id'] ?>"

                        onclick="return confirm('Delete this communication?')">

                        Delete

                        </a>

                        <?php endif; ?>

                    </td>

                </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</article>

</section>

</main>

<?php include __DIR__.'/../includes/footer.php'; ?>