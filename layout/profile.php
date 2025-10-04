<?php
// 1. Start the session (Crucial for getting the logged-in user's ID)
session_start();

// 2. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in (adjust path relative to this file)
    header("Location: ../index.php"); 
    exit();
}

// 3. Include the database connection file (correct relative path to /database/db_connection.php)
require_once __DIR__ . '/../database/db_connection.php';

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];
$uploadMessage = null; // for success/error alerts

// Retrieve flash message from previous request (if any)
if (isset($_SESSION['uploadMessage']) && !empty($_SESSION['uploadMessage'])) {
    $uploadMessage = $_SESSION['uploadMessage'];
    unset($_SESSION['uploadMessage']); // one-time use
}

// Handle profile picture upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $file = $_FILES['profile_pic'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Basic validations
        if ($file['size'] > $maxSize) {
            $uploadMessage = ['type' => 'error', 'text' => 'File is too large. Max 5MB.'];
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!isset($allowedMime[$detected])) {
                $uploadMessage = ['type' => 'error', 'text' => 'Invalid image type.'];
            } else {
                // Create uploads/images if not exists
                $relDir = '/inventory/uploads/images';
                $absDir = $_SERVER['DOCUMENT_ROOT'] . $relDir;
                if (!is_dir($absDir)) {
                    mkdir($absDir, 0775, true);
                }
                // Fetch current profile_pic to delete later if custom
                $currentPic = null;
                $stmtCur = $conn->prepare('SELECT profile_pic FROM users WHERE id = ?');
                $stmtCur->bind_param('i', $user_id);
                $stmtCur->execute();
                $resCur = $stmtCur->get_result();
                if ($rowCur = $resCur->fetch_assoc()) {
                    $currentPic = $rowCur['profile_pic'];
                }
                $stmtCur->close();
                // Generate safe unique filename
                $ext = $allowedMime[$detected];
                $newName = 'pp_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $absPath = $absDir . '/' . $newName;
                if (move_uploaded_file($file['tmp_name'], $absPath)) {
                    $webPath = $relDir . '/' . $newName; // store relative web path
                    $stmtUp = $conn->prepare('UPDATE users SET profile_pic = ?, updated_at = NOW() WHERE id = ?');
                    $stmtUp->bind_param('si', $webPath, $user_id);
                    if ($stmtUp->execute()) {
                        // Delete old file if it was a custom one (and not default placeholder)
                        if ($currentPic && !str_contains($currentPic, 'default_profile.png')) {
                            $oldAbs = $_SERVER['DOCUMENT_ROOT'] . $currentPic;
                            if (is_file($oldAbs)) @unlink($oldAbs);
                        }
                        $uploadMessage = ['type' => 'success', 'text' => 'Profile picture updated successfully'];
                    } else {
                        $uploadMessage = ['type' => 'error', 'text' => 'Database update failed'];
                    }
                    $stmtUp->close();
                } else {
                    $uploadMessage = ['type' => 'error', 'text' => 'Failed to move uploaded file'];
                }
            }
        }
    } else {
        $uploadMessage = ['type' => 'error', 'text' => 'Upload error code: ' . $file['error']];
    }
    // Store flash message & redirect (Prevents form resubmission)
    $_SESSION['uploadMessage'] = $uploadMessage;
    header('Location: ' . basename(__FILE__));
    exit();
}

// Re-fetch user data after potential update
$sql = "SELECT 
            u.employee_id, u.email, u.f_name, u.m_name, u.l_name, u.extensions, 
            u.gender, u.address, u.cp_number, u.status, u.profile_pic, r.role_name
        FROM 
            users u
        JOIN 
            roles r ON u.role_id = r.id
        WHERE 
            u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$stmt->close();
$conn->close();

if (!$user_data) {
    die('Error: User data not found.');
}

// Helper function to format the full name
$full_name = htmlspecialchars($user_data['f_name']);
if (!empty($user_data['m_name'])) {
    $full_name .= ' ' . htmlspecialchars(substr($user_data['m_name'], 0, 1)) . '.';
}
$full_name .= ' ' . htmlspecialchars($user_data['l_name']);

// Define a placeholder for the profile picture if none is set
$raw_profile_pic = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : '/inventory/uploads/images/default_profile.png';
// Ensure it starts with a slash (treat as web-root relative)
if ($raw_profile_pic[0] !== '/') {
    $raw_profile_pic = '/inventory/' . ltrim($raw_profile_pic, '/');
}
// Map to filesystem path for existence check
$filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $raw_profile_pic;
$profile_pic_path = file_exists($filesystem_path) ? htmlspecialchars($raw_profile_pic) : 'https://via.placeholder.com/150/007bff/ffffff?text=User'; // Use a placeholder image if file doesn't exist

// After $user_data is available, add role-based dashboard mapping:
$roleName = isset($user_data['role_name']) ? strtolower($user_data['role_name']) : '';
$dashboardMap = [
    'admin'   => '/inventory/users/admin/dashboard.php',
    'manager' => '/inventory/users/manager/dashboard.php',
    // Adjust if you later add a dedicated staff dashboard page
    'staff'   => '/inventory/users/index.php'
];
$dashboardUrl = $dashboardMap[$roleName] ?? '/inventory/users/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .main-container {
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .profile-card, .data-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 1rem;
            border: none;
        }
        .profile-wrapper {
            position: relative;
            display: inline-block;
        }
        .profile-img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .camera-btn {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: #1e88e5; /* blue accent */
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            transition: background .2s;
        }
        .camera-btn:hover { background: #1565c0; }
        .hidden-file {
            display: none;
        }
        .data-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            margin-bottom: 0;
        }
        .data-value {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .card-header-bg {
            /* Blue gradient replacing red/orange while keeping same angle */
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 1rem;
            text-align: center;
        }
        .status-badge {
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        /* Optional: unify primary accent utility (used for buttons) */
        .btn-accent {
            background-color: #1e88e5 !important;
            border-color: #1e88e5 !important;
        }
        .btn-accent:hover { background-color: #1565c0 !important; border-color: #1565c0 !important; }
    </style>
</head>
<body>
<div class="container main-container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-6 mb-4">
            <div class="card profile-card text-center p-4">
                <div class="profile-wrapper mx-auto mb-3">
                    <img id="profileImage" src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="profile-img">
                    <button class="camera-btn" id="cameraBtn" type="button" title="Change photo">
                        <span class="material-icons">photo_camera</span>
                    </button>
                </div>
                <form id="uploadForm" method="POST" enctype="multipart/form-data" class="d-none">
                    <input type="file" name="profile_pic" id="profileInput" accept="image/*" class="hidden-file">
                </form>
                <h4 class="card-title text-primary mb-3">My Profile</h4>
                
                <div class="mb-3">
                    <p class="data-label">Name</p>
                    <p class="data-value mb-0"><?php echo $full_name; ?></p>
                </div>

                <div class="mb-3">
                    <p class="data-label">Contact Number</p>
                    <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['cp_number'] ?? 'N/A'); ?></p>
                </div>

                <div class="mb-3">
                    <p class="data-label">Email Address</p>
                    <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h6 class="mb-0 text-muted">Account Status</h6>
                    <?php 
                        $status_class = $user_data['status'] == 'Active' ? 'bg-success text-white' : ($user_data['status'] == 'Suspended' ? 'bg-danger text-white' : 'bg-secondary text-white');
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo ucfirst(htmlspecialchars($user_data['status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-md-6">
            <div class="card data-card mb-4">
                <div class="card-header-bg">
                    <h5 class="mb-0">Employee Details</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <p class="data-label">Employee ID</p>
                            <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['employee_id']); ?></p>
                        </div>
                        <div class="col-6 mb-3">
                            <p class="data-label">Role</p>
                            <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['role_name']); ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <p class="data-label">Gender</p>
                            <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['gender'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-6 mb-3">
                            <p class="data-label">Extension</p>
                            <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['extensions'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="data-label">Address</p>
                        <p class="data-value mb-0"><?php echo htmlspecialchars($user_data['address'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="alert alert-info small mt-2 mb-0 d-flex justify-content-center" role="alert" style="font-size: .8rem;">
                        If any details are wrong kindly contact Super Admin.
                    </div>
                </div>
            </div>
                        <?php
                            // Prefer last visited page if stored and not the profile itself
                            $lastPage = isset($_SESSION['last_page_url']) ? $_SESSION['last_page_url'] : null;
                            // Basic safety: ensure it's an internal link (starts with /inventory)
                            if ($lastPage && strpos($lastPage, '/inventory') !== 0) { $lastPage = null; }
                            // If last page is the same as this profile page, null it to fallback
                            if ($lastPage && stripos($lastPage, '/layout/profile.php') !== false) { $lastPage = null; }
                            $targetHref = $lastPage ?: $dashboardUrl;
                            $btnText = $lastPage ? 'Go back' : 'Go to Dashboard';
                        ?>
                        <div class="text-center">
                            <a href="<?php echo htmlspecialchars($targetHref); ?>" class="btn btn-primary btn-lg w-75 btn-accent">
                                <?php echo htmlspecialchars($btnText); ?>
                            </a>
                        </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cameraBtn = document.getElementById('cameraBtn');
const profileInput = document.getElementById('profileInput');
const uploadForm = document.getElementById('uploadForm');
const profileImage = document.getElementById('profileImage');
const MAX_SIZE = 5 * 1024 * 1024; // 5MB
cameraBtn.addEventListener('click', () => profileInput.click());
profileInput.addEventListener('change', () => {
  if (profileInput.files && profileInput.files[0]) {
    const file = profileInput.files[0];
    if (file.size > MAX_SIZE) {
    Swal.fire({ icon: 'error', title: 'Too Large', text: 'File too large (max 5MB)', timer: 4000, showConfirmButton: false, timerProgressBar:true });
      profileInput.value='';
      return;
    }
    const reader = new FileReader();
    reader.onload = e => { profileImage.src = e.target.result; };
    reader.readAsDataURL(file);
    uploadForm.submit();
  }
});
<?php if ($uploadMessage): ?>
Swal.fire({
  toast: true,
  position: 'top-end',
  icon: '<?php echo $uploadMessage['type']; ?>',
  title: '<?php echo $uploadMessage['type']==='success' ? 'Success' : 'Upload Error'; ?>',
  text: '<?php echo htmlspecialchars($uploadMessage['text'], ENT_QUOTES); ?>',
    timer: 4000,
  showConfirmButton: false,
  timerProgressBar: true
});
<?php endif; ?>
</script>
</body>
</html>