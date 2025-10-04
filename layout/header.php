<?php
if (session_status() == PHP_SESSION_NONE) session_start();
// Optionally define a base URL once (can move to a config file)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/inventory');
}
require $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

// Track last visited page (exclude profile.php itself to avoid redirect loop)
if ($user_id) {
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    if ($currentPath && stripos($currentPath, '/layout/profile.php') === false) {
        $_SESSION['last_page_url'] = $currentPath; // store raw request URI
    }
}

if ($user_id && $conn) {
    $stmt = $conn->prepare("SELECT f_name, l_name, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    $user = ['f_name'=>'Guest','l_name'=>'User','profile_pic'=>null];
}

$profilePic = !empty($user['profile_pic'])
    ? (strpos($user['profile_pic'], '/') === 0 ? $user['profile_pic'] : BASE_URL . '/' . ltrim($user['profile_pic'], '/'))
    : BASE_URL . '/uploads/images/default_profile.png';

$conn->close();
?>

<style>
    header{
        font-family: 'Poppins', sans-serif;
    }
</style>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>

<header class="d-flex justify-content-between align-items-center p-2 bg-light shadow-sm" style="height: 60px; margin-left:250px;" id="header">
    <!-- Hamburger -->
    <div class="d-flex align-items-center">
        <span class="material-icons" id="hamburger" style="cursor:pointer; font-size:28px;">menu</span>
    </div>

    <!-- Date & Time -->
    <div class="text-center flex-grow-1">
        <span id="currentDateTime" class="fw-bold"></span>
    </div>

    <!-- Profile Pic & Name -->
    <div class="d-flex align-items-center">
        <a href="/inventory/layout/profile.php" style="text-decoration:none; color:black;" class="d-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Profile">
            <img src="<?php echo $profilePic; ?>" alt="Profile" class="rounded-circle" width="40" height="40">
            <span class="ms-2"><?php echo $user['f_name'] . ' ' . $user['l_name']; ?></span>
        </a>
    </div>
</header>

<script>
// Update date & time
function updateDateTime() {
    const now = new Date();
    const dateOptions = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    const timeOptions = { hour:'numeric', minute:'2-digit', second:'2-digit', hour12:true };
    document.getElementById('currentDateTime').textContent =
        `${now.toLocaleDateString('en-US', dateOptions)} | ${now.toLocaleTimeString('en-US', timeOptions)}`;
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Sidebar toggle
document.getElementById('hamburger').addEventListener('click', function(){
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');

    // Adjust header & content margin dynamically
    const header = document.getElementById('header');
    const content = document.querySelector('.content'); // make sure your main content has class="content"
    if(sidebar.classList.contains('collapsed')){
        header.style.marginLeft = '70px';
        if(content) content.style.marginLeft = '70px';
    } else {
        header.style.marginLeft = '250px';
        if(content) content.style.marginLeft = '250px';
    }
});

// Enable Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function(el){ return new bootstrap.Tooltip(el); });
</script>
