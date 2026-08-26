<aside class="sidebar">

    <!-- =========================
         BRAND
    ========================== -->

    <div class="brand-block">

        <div class="brand">

            <div class="brand-logo">
                <img
                    src="<?= BASE_URL ?>assets/images/Unire-Business-Solutions-Pvt-Ltd.png"
                    alt="Unire Logo"
                >
            </div>

            <div class="brand-text">
                <p>Enterprise Edition</p>
            </div>

        </div>

    </div>


    <?php
    $userRole = $_SESSION['user']['role'] ?? '';
    ?>


    <!-- =========================
         SIDEBAR NAVIGATION
    ========================== -->

    <nav class="side-nav">


        <!-- DASHBOARD -->
        <a
            href="<?= BASE_URL ?>index.php"
            class="<?= active_nav('index.php') ?>"
        >
            <i class="fa-solid fa-table-cells-large"></i>
            <span>Dashboard</span>
        </a>


        <!-- COMPANIES -->
        <a
            href="<?= BASE_URL ?>modules/company_list.php"
            class="<?= active_nav('company_list.php') ?>"
        >
            <i class="fa-solid fa-building"></i>
            <span>Companies</span>
        </a>


        <!-- CLIENTS -->
        <a
            href="<?= BASE_URL ?>modules/clients.php"
            class="<?= active_nav('clients.php') ?>"
        >
            <i class="fa-solid fa-users"></i>
            <span>Clients</span>
        </a>


        <!-- BUSINESS CARDS -->
        <!-- Currently disabled -->
        <!--
        <a
            href="<?= BASE_URL ?>modules/cards.php"
            class="<?= active_nav('cards.php') ?>"
        >
            <i class="fa-regular fa-image"></i>
            <span>Business Cards</span>
        </a>
        -->


        <!-- FOLLOW-UP -->
        <a
            href="<?= BASE_URL ?>modules/followups.php"
            class="<?= active_nav('followups.php') ?>"
        >
            <i class="fa-regular fa-bell"></i>
            <span>Follow-up</span>
        </a>


        <!-- MEETINGS -->
        <a
            href="<?= BASE_URL ?>modules/meetings.php"
            class="<?= active_nav('meetings.php') ?>"
        >
            <i class="fa-solid fa-handshake"></i>
            <span>Meetings</span>
        </a>


        <!-- CALENDAR -->
        <a
            href="<?= BASE_URL ?>modules/calendar.php"
            class="<?= active_nav('calendar.php') ?>"
        >
            <i class="fa-regular fa-calendar-days"></i>
            <span>Calendar</span>
        </a>


        <!-- REPORTS -->
        <!-- All users -->
        <a
            href="<?= BASE_URL ?>modules/reports.php"
            class="<?= active_nav('reports.php') ?>"
        >
            <i class="fa-solid fa-chart-column"></i>
            <span>Reports</span>
        </a>


        <!-- SEARCH -->
        <!-- All users -->
        <a
            href="<?= BASE_URL ?>modules/search.php"
            class="<?= active_nav('search.php') ?>"
        >
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Search</span>
        </a>


        <!-- =========================
             SUPER ADMIN ONLY
        ========================== -->

        <?php if ($userRole === 'Super Admin'): ?>


            <!-- USERS -->
            <a
                href="<?= BASE_URL ?>modules/users.php"
                class="<?= active_nav('users.php') ?>"
            >
                <i class="fa-regular fa-user"></i>
                <span>Users</span>
            </a>


            <!-- SETTINGS -->
            <a
                href="<?= BASE_URL ?>modules/settings.php"
                class="<?= active_nav('settings.php') ?>"
            >
                <i class="fa-solid fa-sliders"></i>
                <span>Settings</span>
            </a>


        <?php endif; ?>


        <!-- PROFILE -->
        <!-- All users -->
        <a
            href="<?= BASE_URL ?>modules/profile.php"
            class="<?= active_nav('profile.php') ?>"
        >
            <i class="fa-regular fa-circle-user"></i>
            <span>Profile</span>
        </a>


    </nav>


    <!-- =========================
         LOGGED-IN USER
    ========================== -->

    <div class="sidebar-footer">

        <strong>
            <?= esc($_SESSION['user']['role'] ?? 'User') ?>
        </strong>

        <span>
            <?= esc($_SESSION['user']['name'] ?? '') ?>
        </span>

    </div>

</aside>