<aside class="sidebar">
        <div class="brand-block">
                <div class="brand-icon">VC</div>
                <div>
                        <h1>CRM Portal</h1>
                        <p>Enterprise Edition</p>
                </div>
        </div>
        <nav class="side-nav">
                <a href="<?= BASE_URL ?>index.php" class="<?= active_nav('index.php') ?>"><i
                                class="fa-solid fa-table-cells-large"></i><span>Dashboard</span></a>
                <a href="<?= BASE_URL ?>modules/company_list.php"><i
                                class="fa-solid fa-building"></i><span>Companies</span></a>
                <a href="<?= BASE_URL ?>modules/clients.php" class="<?= active_nav('clients.php') ?>"><i
                                class="fa-solid fa-users"></i><span>Clients</span></a>
                <!-- <a href="<?= BASE_URL ?>modules/cards.php" class="<?= active_nav('cards.php') ?>"><i
                                class="fa-regular fa-image"></i><span>Business Cards</span></a> -->
                <a href="<?= BASE_URL ?>modules/followups.php" class="<?= active_nav('followups.php') ?>"><i
                                class="fa-regular fa-bell"></i><span>Follow-up</span></a>

                <!-- <a href="<?= BASE_URL ?>modules/communications.php" class="<?= active_nav('communications.php') ?>">
                        <i class="fa-solid fa-comments"></i>
                        <span>Communications</span>
                </a> -->

                <a href="<?= BASE_URL ?>modules/meetings.php" class="<?= active_nav('meetings.php') ?>">
                        <i class="fa-solid fa-handshake"></i>
                        <span>Meetings</span>
                </a>
                <a href="<?= BASE_URL ?>modules/calendar.php" class="<?= active_nav('calendar.php') ?>"><i
                                class="fa-regular fa-calendar-days"></i><span>Calendar</span></a>
                <a href="<?= BASE_URL ?>modules/reports.php" class="<?= active_nav('reports.php') ?>"><i
                                class="fa-solid fa-chart-column"></i><span>Reports</span></a>
                <a href="<?= BASE_URL ?>modules/search.php" class="<?= active_nav('search.php') ?>"><i
                                class="fa-solid fa-magnifying-glass"></i><span>Search</span></a>
                <a href="<?= BASE_URL ?>modules/users.php" class="<?= active_nav('users.php') ?>"><i
                                class="fa-regular fa-user"></i><span>Users</span></a>
                <a href="<?= BASE_URL ?>modules/settings.php" class="<?= active_nav('settings.php') ?>"><i
                                class="fa-solid fa-sliders"></i><span>Settings</span></a>
                <a href="<?= BASE_URL ?>modules/profile.php" class="<?= active_nav('profile.php') ?>"><i
                                class="fa-regular fa-circle-user"></i><span>Profile</span></a>
        </nav>
        <div class="sidebar-footer">
                <strong><?= esc($_SESSION['user']['role'] ?? 'User') ?></strong><span><?= esc($_SESSION['user']['name'] ?? '') ?></span>
        </div>
</aside>