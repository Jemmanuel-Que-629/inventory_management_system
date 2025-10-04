<?php
session_start();

// Require login & admin role guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../database/db_connection.php';

// Fetch current user role to restrict access (only admin allowed here)
$currentUserId = $_SESSION['user_id'];
$roleStmt = $conn->prepare('SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$roleStmt->bind_param('i', $currentUserId);
$roleStmt->execute();
$roleRes = $roleStmt->get_result();
$roleRow = $roleRes->fetch_assoc();
$roleStmt->close();

if (!$roleRow || strtolower($roleRow['role_name']) !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

// Fetch all users for masterlist
$sql = "SELECT u.id, u.employee_id, u.email, u.f_name, u.m_name, u.l_name, u.extensions, 
               u.gender, u.address, u.cp_number, u.role_id, u.status, u.profile_pic, 
               u.created_at, u.updated_at, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.created_at DESC";

$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Masterlist | Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Tabulator CSS -->
    <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        #sidebar.collapsed + .content,
        #sidebar.collapsed ~ .content {
            margin-left: 70px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        /* Action Menu Styles */
        .action-menu {
            position: relative;
            display: inline-block;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #6c757d;
        }
        
        .action-btn:hover {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .action-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            min-width: 160px;
            padding: 6px 0;
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
            display: none;
            z-index: 5000;
            margin-top: 4px;
        }
        
        .action-dropdown.show {
            display: block;
        }
        
        .action-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            font-size: 13px;
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }
        
        .action-item:hover {
            background: #f1f3f5;
        }
        
        .action-item.danger:hover {
            background:#ffe3e3;
            color:#c92a2a;
        }
        
        .action-btn {
            background: transparent;
            border: none;
            cursor:pointer;
            padding:4px 6px;
            border-radius:6px;
        }
        
        .action-btn:hover {
            background:#e9ecef;
        }
        
        .action-btn:focus {
            outline: 2px solid #adb5bd;
        }
        
        /* Status Badge Styles */
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-inactive {
            background-color: #e2e3e5;
            color: #41464b;
        }
        
        .status-suspended {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Role Badge Styles */
        .role-admin {
            background-color: #cff4fc;
            color: #055160;
        }
        
        .role-manager {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .role-staff {
            background-color: #e2e3e5;
            color: #41464b;
        }
        
        /* Custom Button Styles */
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
        }
        
        .btn-outline-secondary {
            border-radius: 8px;
        }
        
        /* Tabulator Customizations */
        .tabulator {
            border: none;
            background: transparent;
        }
        
        .tabulator .tabulator-header {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tabulator .tabulator-col {
            background: transparent;
        }
        
        .tabulator .tabulator-row:hover {
            background-color: #f8f9fa;
        }
        /* Remove blank filler column */
        .tabulator .tabulator-filler { display:none !important; }
        
        /* --- Action dropdown fix additions --- */
        .tabulator, .tabulator .tabulator-tableholder, .tabulator .tabulator-table { overflow: visible !important; }
        .tabulator-row { overflow: visible !important; }
        .tabulator-cell { overflow: visible !important; position: relative; }
        .action-menu { position: relative; }
        .action-dropdown { 
          position: absolute; 
          top: 100%; 
          right: 0; 
          background: #fff; 
          border: 1px solid #dee2e6; 
          border-radius: 8px; 
          min-width: 160px; 
          padding: 6px 0; 
          box-shadow: 0 6px 18px rgba(0,0,0,0.15); 
          display: none; 
          z-index: 5000; 
        }
        .action-dropdown.show { display: block; }
        .action-item { display: flex; align-items: center; gap: 8px; padding: 6px 14px; font-size: 13px; text-decoration: none; color: #333; cursor: pointer; }
        .action-item:hover { background: #f1f3f5; }
        .action-item.danger:hover { background:#ffe3e3; color:#c92a2a; }
        .action-btn { background: transparent; border: none; cursor:pointer; padding:4px 6px; border-radius:6px; }
        .action-btn:hover { background:#e9ecef; }
        .action-btn:focus { outline: 2px solid #adb5bd; }
        
        /* Ensure parent containers don't clip */
        .table-card { overflow: visible; }
        
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include_once __DIR__ . '/../../layout/sidebar.php'; ?>
    
    <!-- Include Header -->
    <?php include_once __DIR__ . '/../../layout/header.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">User Masterlist</h2>
                    <p class="mb-0 opacity-75">Manage all users in the system</p>
                </div>
                <button id="addUserBtn" class="btn btn-light">
                    <span class="material-icons align-middle me-2" style="font-size: 20px;">person_add</span>
                    Add User
                </button>
            </div>
        </div>
        
        
        <!-- Data Table -->
        <div class="table-card">
            <div id="userTable"></div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Tabulator JS -->
    <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
    
    <script>
        // User data from PHP
        const userData = <?php echo json_encode($users); ?>;
        
        // Initialize Tabulator
        let table;
        
        $(document).ready(function() {
            initializeTable();
            setupEventHandlers();
        });
        
        function initializeTable() {
            table = new Tabulator("#userTable", {
                data: userData,
                layout: "fitColumns", // keep columns stretched but hide filler via CSS
                responsiveLayout: "hide",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [5, 10, 25, 50],
                movableColumns: true,
                resizableRows: true,
                placeholder: "No users found matching your criteria.",
                initialSort: [
                    {column: "created_at", dir: "desc"}
                ],
                columns: [
                    {
                        title: "Employee ID",
                        field: "employee_id",
                        width: 120,
                        headerFilter: "input"
                    },
                    {
                        title: "Full Name",
                        field: "full_name",
                        width: 200,
                        formatter: function(cell) {
                            const data = cell.getRow().getData();
                            let name = data.f_name || '';
                            if (data.m_name) {
                                name += ' ' + data.m_name.charAt(0) + '.';
                            }
                            name += ' ' + (data.l_name || '');
                            return name;
                        },
                        headerFilter: "input"
                    },
                    {
                        title: "Email",
                        field: "email",
                        width: 250,
                        headerFilter: "input"
                    },
                    {
                        title: "Role",
                        field: "role_name",
                        width: 100,
                        formatter: function(cell) {
                            const role = cell.getValue();
                            const roleClass = `role-${role.toLowerCase()}`;
                            return `<span class="badge ${roleClass}">${role}</span>`;
                        },
                        headerFilter: "select",
                        headerFilterParams: {
                            values: {"": "All", "Admin": "Admin", "Manager": "Manager", "Staff": "Staff"}
                        }
                    },
                    {
                        title: "Status",
                        field: "status",
                        width: 100,
                        formatter: function(cell) {
                            const status = cell.getValue();
                            const statusClass = `status-${status.toLowerCase()}`;
                            return `<span class="badge ${statusClass}">${status}</span>`;
                        },
                        headerFilter: "select",
                        headerFilterParams: {
                            values: {"": "All", "Active": "Active", "Inactive": "Inactive", "Suspended": "Suspended"}
                        }
                    },
                    {
                        title: "Created",
                        field: "created_at",
                        width: 120,
                        formatter: function(cell) {
                            const date = new Date(cell.getValue());
                            return date.toLocaleDateString('en-US');
                        }
                    },
                    {
                        title: "Actions",
                        field: "actions",
                        width: 100,
                        headerSort: false,
                        formatter: function(cell) {
                            const userId = cell.getRow().getData().id;
                            return `
                                <div class="action-menu">
                                    <button class="action-btn" data-user-id="${userId}">
                                        <span class="material-icons">more_vert</span>
                                    </button>
                                    <div class="action-dropdown">
                                        <a class="action-item" data-action="view" data-user-id="${userId}">
                                            <span class="material-icons">visibility</span>
                                            View
                                        </a>
                                        <a class="action-item" data-action="edit" data-user-id="${userId}">
                                            <span class="material-icons">edit</span>
                                            Edit
                                        </a>
                                        <a class="action-item" data-action="archive" data-user-id="${userId}">
                                            <span class="material-icons">archive</span>
                                            Archive
                                        </a>
                                        <a class="action-item danger" data-action="delete" data-user-id="${userId}">
                                            <span class="material-icons">delete</span>
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    }
                ]
            });
        }
        
        // Composite filter state
        let filterRole = "";
        let filterStatus = "";
        let searchTerm = "";

        function applyCompositeFilter(){
            table.clearFilter();
            table.setFilter(function(data){
                // Role filter
                if(filterRole && data.role_name !== filterRole) return false;
                // Status filter
                if(filterStatus && data.status !== filterStatus) return false;
                // Search term (first, middle, last, email, employee id)
                if(searchTerm){
                    const haystack = [data.f_name, data.m_name, data.l_name, data.email, data.employee_id]
                        .filter(Boolean)
                        .map(v => v.toString().toLowerCase());
                    const matched = haystack.some(val => val.includes(searchTerm));
                    if(!matched) return false;
                }
                return true;
            });
        }

        function setupEventHandlers() {
            // Action button click
            $(document).on('click', '.action-btn', function(e) {
                e.stopPropagation();
                const dropdown = $(this).siblings('.action-dropdown');
                $('.action-dropdown').removeClass('show');
                dropdown.toggleClass('show');
            });

            // Action item click
            $(document).on('click', '.action-item', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const userId = $(this).data('user-id');
                $('.action-dropdown').removeClass('show');
                handleAction(action, userId);
            });

            // Close dropdown when clicking outside
            $(document).on('click', function() { $('.action-dropdown').removeClass('show'); });

            // Role filter
            $('#roleFilter').on('change', function(){ filterRole = this.value; applyCompositeFilter(); });
            // Status filter
            $('#statusFilter').on('change', function(){ filterStatus = this.value; applyCompositeFilter(); });
            // Unified search
            $('#searchInput').on('keyup', function(){ searchTerm = this.value.trim().toLowerCase(); applyCompositeFilter(); });

            // Reset filters
            $('#resetFilters').on('click', function(){
                filterRole = ""; filterStatus = ""; searchTerm = "";
                $('#roleFilter').val("");
                $('#statusFilter').val("");
                $('#searchInput').val("");
                applyCompositeFilter();
                showToast('info','Filters Reset','All filters have been cleared');
            });

            // Add user button
            $('#addUserBtn').on('click', function(){
                showToast('info','Add User','Add user functionality will be implemented here');
            });
        }
        
        function handleAction(action, userId) {
            const user = userData.find(u => u.id == userId);
            const userName = `${user.f_name} ${user.l_name}`;
            
            switch(action) {
                case 'view':
                    showToast('info', 'View User', `Viewing details for ${userName}`);
                    // Implement view user modal
                    break;
                    
                case 'edit':
                    showToast('info', 'Edit User', `Editing ${userName}`);
                    // Implement edit user modal
                    break;
                    
                case 'archive':
                    Swal.fire({
                        title: 'Archive User?',
                        text: `Are you sure you want to archive ${userName}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, archive it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Implement archive functionality
                            showToast('success', 'User Archived', `${userName} has been archived`);
                        }
                    });
                    break;
                    
                case 'delete':
                    Swal.fire({
                        title: 'Delete User?',
                        text: `Are you sure you want to delete ${userName}? This action cannot be undone!`,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Implement delete functionality
                            showToast('success', 'User Deleted', `${userName} has been deleted`);
                        }
                    });
                    break;
            }
        }
        
        function showToast(icon, title, text) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: icon,
                title: title,
                text: text
            });
        }
        
        /* --- Action dropdown JS fix additions --- */
        (function(){
          function closeAllActionMenus(){ document.querySelectorAll('.action-dropdown.show').forEach(dd=>dd.classList.remove('show')); }
          document.addEventListener('click', function(e){
            if(!e.target.closest('.action-menu')) closeAllActionMenus();
          });
          document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeAllActionMenus(); });
          // Delegate after table renders
          document.addEventListener('click', function(e){
            const btn = e.target.closest('.action-btn');
            if(btn){
              e.preventDefault();
              e.stopPropagation();
              const menu = btn.parentElement.querySelector('.action-dropdown');
              if(!menu) return;
              const isOpen = menu.classList.contains('show');
              closeAllActionMenus();
              if(!isOpen){
                menu.classList.add('show');
                // Optional smart positioning if near viewport bottom
                const rect = menu.getBoundingClientRect();
                if(rect.bottom > window.innerHeight){
                  menu.style.top = 'auto';
                  menu.style.bottom = '100%';
                }
              }
            }
          }, true);
        })();
    </script>
</body>
</html>
