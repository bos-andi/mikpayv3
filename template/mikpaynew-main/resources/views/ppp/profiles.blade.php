@extends('layouts.dashboard')

@section('title', 'PPP Profiles')
@section('heading', 'PPP Profiles')

@section('content')
<style>
.ppp-modal-backdrop{
    position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:1200;padding:16px;
}
.ppp-modal-backdrop.show{display:flex;}
.ppp-modal{
    width:min(760px,96vw);max-height:90vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 70px rgba(2,6,23,.35);
}
.ppp-modal-header{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;background:linear-gradient(135deg,#312e81 0%,#4f46e5 100%);color:#fff;}
.ppp-modal-header h4{margin:0;display:flex;align-items:center;gap:10px;font-weight:700;}
.ppp-modal-header h4 i{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:13px;}
.ppp-modal-close{border:none;background:rgba(255,255,255,.12);font-size:20px;line-height:1;cursor:pointer;color:#fff;width:30px;height:30px;border-radius:8px;}
.ppp-modal-close:hover{background:rgba(255,255,255,.2);}
.ppp-modal-body{padding:18px;}
.profile-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 14px;}
.profile-field{margin:0;}
.profile-field.full{grid-column:1/-1;}
.profile-field .form-label{font-size:12px;font-weight:700;color:#344054;margin-bottom:6px;letter-spacing:.2px;}
.profile-field .form-control{height:42px;border-radius:10px;border:1px solid #d0d5dd;box-shadow:none;font-size:13px;color:#101828;}
.profile-field .form-control:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12);}
.profile-form-actions{margin-top:14px;padding-top:12px;border-top:1px solid #eef2f6;display:flex;justify-content:flex-end;gap:10px;}
.profile-btn{border:none;border-radius:10px;padding:10px 16px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:8px;}
.profile-btn.primary{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;}
.profile-btn.secondary{background:#eef2f7;color:#334155;}
@media (max-width: 768px){.profile-form-grid{grid-template-columns:1fr;}}
</style>
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fa fa-pie-chart"></i> PPP Profiles — {{ count($profiles) }} item
            <span style="float:right;">
                <button type="button" class="btn bg-success text-white" onclick="openAddProfileModal()"><i class="fa fa-plus"></i> Tambah PPP Profile</button>
            </span>
        </h3>
    </div>
    <div class="card-body">
        <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
        <div class="overflow box-bordered" style="max-height: 75vh;">
            <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Local Address</th>
                        <th>Remote Address</th>
                        <th>Rate Limit</th>
                        <th>Only One</th>
                        <th>Address List</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p['name'] ?? '-' }}</td>
                        <td>{{ $p['local-address'] ?? '-' }}</td>
                        <td>{{ $p['remote-address'] ?? '-' }}</td>
                        <td>{{ $p['rate-limit'] ?? '-' }}</td>
                        <td>{{ $p['only-one'] ?? '-' }}</td>
                        <td>{{ $p['address-list'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada profile.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: Add Profile --}}
<div class="ppp-modal-backdrop" id="addProfileModal" onclick="closeProfileModalByBackdrop(event, 'addProfileModal')">
    <div class="ppp-modal">
        <div class="ppp-modal-header">
            <h4><i class="fa fa-plus"></i> Tambah PPP Profile</h4>
            <button type="button" class="ppp-modal-close" onclick="closeProfileModal('addProfileModal')">&times;</button>
        </div>
        <div class="ppp-modal-body">
            <form method="POST" action="{{ route('ppp.store-profile') }}">
                @csrf
                <div class="profile-form-grid">
                    <div class="profile-field">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="contoh: 10M">
                    </div>
                    <div class="profile-field">
                        <label class="form-label">Rate Limit</label>
                        <input type="text" name="rate-limit" class="form-control" placeholder="contoh: 10M/10M">
                    </div>
                    <div class="profile-field">
                        <label class="form-label">Local Address</label>
                        <input type="text" name="local-address" class="form-control" placeholder="opsional">
                    </div>
                    <div class="profile-field">
                        <label class="form-label">Remote Address</label>
                        <input type="text" name="remote-address" class="form-control" placeholder="opsional">
                    </div>
                    <div class="profile-field">
                        <label class="form-label">Only One</label>
                        <select name="only-one" class="form-control">
                            <option value="">-- default --</option>
                            <option value="yes">yes</option>
                            <option value="no">no</option>
                            <option value="default">default</option>
                        </select>
                    </div>
                    <div class="profile-field">
                        <label class="form-label">Address List</label>
                        <input type="text" name="address-list" class="form-control" placeholder="opsional">
                    </div>
                    <div class="profile-field full">
                        <label class="form-label">Comment</label>
                        <input type="text" name="comment" class="form-control" placeholder="catatan tambahan (opsional)">
                    </div>
                </div>
                <div class="profile-form-actions">
                    <button type="button" class="profile-btn secondary" onclick="closeProfileModal('addProfileModal')"><i class="fa fa-times"></i> Batal</button>
                    <button type="submit" class="profile-btn primary"><i class="fa fa-save"></i> Simpan Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('filterTable')?.addEventListener('keyup', function() {
    var v = this.value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().indexOf(v) > -1 ? '' : 'none';
    });
});

function openProfileModal(id) {
    document.getElementById(id)?.classList.add('show');
}
function closeProfileModal(id) {
    document.getElementById(id)?.classList.remove('show');
}
function closeProfileModalByBackdrop(event, id) {
    if (event.target && event.target.id === id) {
        closeProfileModal(id);
    }
}
function openAddProfileModal() {
    openProfileModal('addProfileModal');
}
</script>
@endpush
@endsection
