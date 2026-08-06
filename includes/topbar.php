<header class="topbar">
    <div>
        <h2><?= esc(page_title()) ?></h2>
        <p><?= esc(page_description()) ?></p>
    </div>
    <div class="topbar-actions">
        <form class="search-bar" method="get" action="<?= BASE_URL ?>modules/search.php">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" placeholder="Search clients, company, phone..." value="<?= esc($_GET['q'] ?? '') ?>">
        </form>
        <a class="btn btn-outline" href="<?= BASE_URL ?>auth/logout.php">Logout</a>
    </div>
</header>
