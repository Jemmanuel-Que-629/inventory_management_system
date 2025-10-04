<!-- Google Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Sidebar styling */
#sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background-color: #212529;
    color: white;
    transition: width 0.3s;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    font-family: 'Poppins', sans-serif;
}

/* Collapsed sidebar */
#sidebar.collapsed {
    width: 70px;
}
#sidebar.collapsed .sidebar-text {
    display: none;
}
#sidebar.collapsed .sidebar-logo {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}
#sidebar.collapsed .nav-link {
    justify-content: center !important;
    padding: 0.5rem 0;
}
#sidebar.collapsed .material-icons {
    margin-right: 0 !important;
    margin-left: 0 !important;
}
#sidebar.collapsed img {
    max-width: 40px;
    margin: 10px auto;
}

/* Sidebar logo / profile image */
#sidebar img {
    width: 100%;
    max-width: 150px;
    height: auto;
    border-radius: 50%;
    transition: all 0.3s;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

/* Hover effect */
.nav-link:hover {
    background-color: #495057;
    border-radius: 5px;
    transition: 0.2s;
}

/* Small screen: horizontal bottom bar */
@media (max-width: 768px) {
    #sidebar {
        width: 100%;
        height: 60px;
        flex-direction: row;
        bottom: 0;
        left: 0;
        top: auto;
        padding: 0 5px;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
    }
    #sidebar .nav-pills {
        flex-direction: row;
        justify-content: start;
        width: auto;
    }
    #sidebar img, #sidebar h5 {
        display: none; /* hide logo/text */
    }
    #sidebar .nav-link {
        justify-content: center;
        padding: 0.3rem 0.5rem;
        font-size: 14px;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
    }
    #sidebar .nav-link .material-icons {
        font-size: 24px;
    }
}

/* Tooltip fix: keep them visible */
.nav-link[data-bs-toggle="tooltip"] {
    position: relative;
}
</style>

<nav id="sidebar" class="d-flex flex-column p-3 text-white justify-content-center">
    <!-- Logo / Header -->
    <div class="sidebar-logo mb-4">
        <img src="../../uploads/images/logo.png" alt="Logo" class="mt-3 mb-3">
        <h5 class="text-center sidebar-text">Inventory Management System</h5>
    </div>

    <!-- Sidebar Menu -->
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link text-white d-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                <span class="material-icons me-2">dashboard</span>
                <span class="sidebar-text">Dashboard</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="masterlist.php" class="nav-link text-white d-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="right" title="Masterlist">
                <span class="material-icons me-2">list_alt</span>
                <span class="sidebar-text">Masterlist</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="activity_logs.php" class="nav-link text-white d-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="right" title="Activity Logs">
                <span class="material-icons me-2">history</span>
                <span class="sidebar-text">Activity Logs</span>
            </a>
        </li>
        <li class="mt-4">
            <a href="../../logout.php" class="nav-link text-white d-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                <span class="material-icons me-2">logout</span>
                <span class="sidebar-text">Logout</span>
            </a>
        </li>
    </ul>
</nav>

<script>
// Initialize Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function(el) {
  return new bootstrap.Tooltip(el);
});
</script>
