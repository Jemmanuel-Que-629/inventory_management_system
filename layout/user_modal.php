<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!-- Global User Modal Include -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title" id="userModalTitle">User</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-0" id="userModalBody">
				<div class="p-4 text-center text-muted small">Loading...</div>
			</div>
			<div class="modal-footer d-flex justify-content-between">
				<div id="userModalStatusArea" class="small text-muted"></div>
				<div>
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary btn-sm d-none" id="userModalPrimaryBtn">Save</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
(function(){
	const modalEl = document.getElementById('userModal');
	if(!modalEl) return;
	let bsModal = null;
	function ensureModal(){ if(!bsModal){ bsModal = new bootstrap.Modal(modalEl); } return bsModal; }
	window.openUserModal = function(action, userId){
			const titleMap = { view:'View User', edit:'Edit User', archive:'Archive User', status:'Update Status' };
			document.getElementById('userModalTitle').textContent = titleMap[action] || 'User';
			document.getElementById('userModalBody').innerHTML = '<div class="p-4 text-center text-muted small">Loading...</div>';
			const primaryBtn = document.getElementById('userModalPrimaryBtn');
			primaryBtn.classList.add('d-none');
			primaryBtn.replaceWith(primaryBtn.cloneNode(true)); // reset listeners
			fetch('/inventory/users/admin/user_modal_content.php?action='+encodeURIComponent(action)+'&id='+encodeURIComponent(userId))
				.then(r=>r.text())
				.then(html=>{
					 document.getElementById('userModalBody').innerHTML = html;
					 const needsSave = ['edit','archive','status'].includes(action);
					 if(needsSave){
						 const newPrimary = document.getElementById('userModalPrimaryBtn');
						 newPrimary.textContent = action==='edit' ? 'Save Changes' : (action==='archive' ? 'Confirm Archive' : 'Update');
						 newPrimary.classList.remove('d-none');
						 newPrimary.onclick = function(){ submitUserModal(action, userId); };
					 }
				})
				.catch(err=>{
					document.getElementById('userModalBody').innerHTML = '<div class="p-4 text-danger small">Failed to load content.</div>';
				});
			ensureModal().show();
	};
	window.submitUserModal = function(action, userId){
			const form = document.querySelector('#userModalBody form');
			if(!form){ return; }
			const fd = new FormData(form);
			fd.append('action', action);
			fd.append('id', userId);
			fetch('/inventory/users/admin/user_actions.php', { method:'POST', body: fd })
				.then(r=>r.json())
				.then(data=>{
					 if(data.success){
						 Swal.fire({toast:true,icon:'success',title:data.message||'Success',position:'top-end',showConfirmButton:false,timer:2000});
						 if(data.refresh){ window.location.reload(); } else { ensureModal().hide(); }
					 } else {
						 Swal.fire({toast:true,icon:'error',title:data.message||'Error',position:'top-end',showConfirmButton:false,timer:2500});
					 }
				})
				.catch(()=>{ Swal.fire({toast:true,icon:'error',title:'Request failed',position:'top-end',showConfirmButton:false,timer:2500}); });
	};
})();
</script>