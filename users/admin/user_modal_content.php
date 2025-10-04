<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo '<div class="p-4 text-danger">Forbidden</div>'; exit; }
$action = $_GET['action'] ?? 'view';
$userId = (int)($_GET['id'] ?? 0);
require_once __DIR__ . '/../../database/db_connection.php';

$stmt = $conn->prepare('SELECT u.id, u.employee_id, u.email, u.f_name, u.m_name, u.l_name, u.extensions, u.gender, u.address, u.cp_number, u.status, r.role_name, u.created_at, u.updated_at FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
$conn->close();
if(!$user){ echo '<div class="p-4 text-danger small">User not found.</div>'; exit; }
$fullName = htmlspecialchars($user['f_name']).' '.(!empty($user['m_name'])?htmlspecialchars(substr($user['m_name'],0,1)).'. ':'').htmlspecialchars($user['l_name']);

function esc($v){ return htmlspecialchars((string)$v); }

switch($action){
        case 'view':
                // Derive profile picture path
                $rawPic = $user['profile_pic'] ?? '';
                if(!$rawPic){ $rawPic = '/inventory/uploads/images/default_profile.png'; }
                if($rawPic[0] !== '/') { $rawPic = '/inventory/' . ltrim($rawPic,'/'); }
                $statusLower = strtolower($user['status']);
                $statusMap = [ 'active'=>'bg-success', 'inactive'=>'bg-secondary', 'suspended'=>'bg-danger' ];
                $statusClass = $statusMap[$statusLower] ?? 'bg-dark';
                $roleBadge = strtoupper(htmlspecialchars($user['role_name']));
                // Length of service (days)
                $createdTs = strtotime($user['created_at']);
                $nowTs = time();
                $diffDays = $createdTs ? floor(($nowTs - $createdTs)/86400) : 0;
                $lengthService = $diffDays >= 30 ? floor($diffDays/30) . ' mo, ' . ($diffDays%30) . ' d' : $diffDays . ' d';
                echo '<div class="p-4">'
                        .'<div class="row g-4 align-items-start">'
                        .'<div class="col-md-4 text-center">'
                            .'<img src="'.esc($rawPic).'" alt="Profile" class="rounded-circle mb-3" style="width:140px; height:140px; object-fit:cover; border:4px solid #f1f3f5; box-shadow:0 4px 12px rgba(0,0,0,.08);">'
                            .'<h5 class="mb-1">'.$fullName.'</h5>'
                            .'<div class="mb-2"><span class="badge bg-secondary">'.$roleBadge.'</span></div>'
                            .'<div><span class="badge '.$statusClass.'">'.strtoupper(esc($statusLower)).'</span></div>'
                        .'</div>'
                        .'<div class="col-md-8">'
                            .'<div class="row small g-3">'
                                .'<div class="col-sm-6"><strong>Employee ID:</strong><div>'.esc($user['employee_id']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Email:</strong><div>'.esc($user['email']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Contact:</strong><div>'.esc($user['cp_number']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Extension:</strong><div>'.esc($user['extensions']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Gender:</strong><div>'.esc($user['gender']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Created:</strong><div>'.esc($user['created_at']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Updated:</strong><div>'.esc($user['updated_at']).'</div></div>'
                                .'<div class="col-sm-6"><strong>Length of Service:</strong><div>'.esc($lengthService).'</div></div>'
                                .'<div class="col-12"><strong>Address:</strong><div>'.esc($user['address']).'</div></div>'
                            .'</div>'
                        .'</div>'
                        .'</div>'
                    .'</div>';
                break;
    case 'edit':
        $suffixes = [''=>'None','Jr.'=>'Jr.','Sr.'=>'Sr.','III'=>'III','IV'=>'IV','V'=>'V'];
        $suffixOptions = '';
        foreach($suffixes as $val=>$label){
            $sel = ($user['extensions']===$val)?'selected':'';
            $suffixOptions .= '<option '.$sel.' value="'.esc($val).'">'.esc($label).'</option>';
        }
        $addrSafe = esc($user['address']);
        echo '<form class="p-4 small" id="editUserForm">'
            .'<div class="row g-3">'
            .'<div class="col-md-3"><label class="form-label mb-1">First Name</label><input name="f_name" value="'.esc($user['f_name']).'" class="form-control form-control-sm" required></div>'
            .'<div class="col-md-3"><label class="form-label mb-1">Middle</label><input name="m_name" value="'.esc($user['m_name']).'" class="form-control form-control-sm"></div>'
            .'<div class="col-md-3"><label class="form-label mb-1">Last Name</label><input name="l_name" value="'.esc($user['l_name']).'" class="form-control form-control-sm" required></div>'
            .'<div class="col-md-3"><label class="form-label mb-1">Suffix</label><select name="extensions" class="form-select form-select-sm">'.$suffixOptions.'</select></div>'
            .'<div class="col-md-6"><label class="form-label mb-1">Email</label><input type="email" name="email" value="'.esc($user['email']).'" class="form-control form-control-sm" required></div>'
            .'<div class="col-md-3"><label class="form-label mb-1">Employee ID</label><input name="employee_id" value="'.esc($user['employee_id']).'" class="form-control form-control-sm"></div>'
            .'<div class="col-md-3"><label class="form-label mb-1">Status</label><select name="status" class="form-select form-select-sm">'
            .'<option '.($user['status']=='active'?'selected':'').' value="active">Active</option>'
            .'<option '.($user['status']=='inactive'?'selected':'').' value="inactive">Inactive</option>'
            .'<option '.($user['status']=='suspended'?'selected':'').' value="suspended">Suspended</option>'
            .'</select></div>'
            .'<div class="col-md-4"><label class="form-label mb-1">Contact</label><input name="cp_number" value="'.esc($user['cp_number']).'" class="form-control form-control-sm"></div>'
            .'<div class="col-md-8"><label class="form-label mb-1">Address</label>'
              .'<div id="addressEditable" class="form-control form-control-sm" style="height:auto; min-height:60px; overflow:auto;" contenteditable="true">'.$addrSafe.'</div>'
              .'<textarea name="address" id="addressHidden" class="d-none">'.$addrSafe.'</textarea>'
              .'<script>document.getElementById("addressEditable").addEventListener("input",function(){document.getElementById("addressHidden").value=this.innerText.trim();});</script>'
            .'</div>'
            .'</div>'
            .'</form>';
        break;
    case 'archive':
        echo '<form class="p-4" id="archiveUserForm">'
            .'<p class="small mb-3">Archiving user <strong>'. $fullName .'</strong> will set their status to <code>inactive</code>. You can re-activate later.</p>'
            .'<div class="form-check mb-2">'
            .'<input class="form-check-input" type="checkbox" value="1" id="confirmArchive" name="confirm" required>'
            .'<label class="form-check-label small" for="confirmArchive">I understand and want to proceed.</label>'
            .'</div>'
            .'</form>';
        break;
    case 'status':
        echo '<form class="p-4 small" id="statusUserForm">'
            .'<p class="mb-2">Change status for <strong>'. $fullName .'</strong></p>'
            .'<select name="status" class="form-select form-select-sm">'
            .'<option '.($user['status']=='active'?'selected':'').' value="active">Active</option>'
            .'<option '.($user['status']=='inactive'?'selected':'').' value="inactive">Inactive</option>'
            .'<option '.($user['status']=='suspended'?'selected':'').' value="suspended">Suspended</option>'
            .'</select>'
            .'</form>';
        break;
    default:
        echo '<div class="p-4 text-danger small">Unknown action.</div>';
}
