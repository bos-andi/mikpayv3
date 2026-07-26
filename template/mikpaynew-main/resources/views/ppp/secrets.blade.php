@extends('layouts.dashboard')

@section('title', 'PPP Secrets')
@section('heading', 'PPP Secrets')

@section('content')
<style>
.ppp-modal-backdrop{
    position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:1200;padding:16px;
}
.ppp-modal-backdrop.show{display:flex;}
.ppp-modal{
    width:min(760px,96vw);max-height:90vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 70px rgba(2,6,23,.35);
}
.ppp-modal-header{
    display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #e5e7eb;
}
.ppp-modal-body{padding:16px;}
.ppp-modal-close{border:none;background:transparent;font-size:20px;line-height:1;cursor:pointer;color:#64748b;}

.ppp-modal-modern .ppp-modal-header{
    padding: 16px 18px;
    background: linear-gradient(135deg, #312e81 0%, #4f46e5 100%);
    border-bottom: none;
    color: #fff;
}
.ppp-modal-modern .ppp-modal-header h4{
    margin:0;
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:700;
}
.ppp-modal-modern .ppp-modal-header h4 i{
    width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.16);
    display:flex;align-items:center;justify-content:center;font-size:13px;
}
.ppp-modal-modern .ppp-modal-close{
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: rgba(255,255,255,.12);
}
.ppp-modal-modern .ppp-modal-close:hover{background: rgba(255,255,255,.2);}
.ppp-modal-modern .ppp-modal-body{padding:18px;}

.secret-form-grid{
    display:grid;
    grid-template-columns: repeat(2,minmax(0,1fr));
    gap: 12px 14px;
}
.secret-field{margin:0;}
.secret-field.full{grid-column:1/-1;}
.secret-field .form-label{
    font-size:12px;
    font-weight:700;
    color:#344054;
    margin-bottom:6px;
    letter-spacing:.2px;
}
.secret-field .form-control{
    height:42px;
    border-radius:10px;
    border:1px solid #d0d5dd;
    box-shadow:none;
    font-size:13px;
    color:#101828;
}
.secret-field .form-control:focus{
    border-color:#4f46e5;
    box-shadow:0 0 0 3px rgba(79,70,229,.12);
}
.secret-form-actions{
    margin-top:14px;
    padding-top:12px;
    border-top:1px solid #eef2f6;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}
.secret-btn{
    border:none;
    border-radius:10px;
    padding:10px 16px;
    font-size:12px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
.secret-btn.primary{
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
}
.secret-btn.secondary{
    background:#eef2f7;
    color:#334155;
}
@media (max-width: 768px){
    .secret-form-grid{grid-template-columns:1fr;}
}
</style>
@php
    $total = count($secrets);
    $online = count($onlineUsers);
    $offline = $total - $online;
@endphp
<div class="ppp-stats" style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
    <div class="ppp-stat-card" style="flex:1;min-width:140px;background:#fff;border-radius:12px;padding:20px;box-shadow:0 0 30px rgba(0,0,0,0.05);">
        <div style="width:50px;height:50px;border-radius:50%;background:rgba(77,68,181,0.1);color:#4D44B5;display:flex;align-items:center;justify-content:center;font-size:24px;"><i class="fa fa-key"></i></div>
        <div style="margin-left:15px;"><h4 style="margin:0;font-size:14px;color:#A098AE;">Total Secrets</h4><p style="margin:5px 0 0;font-size:28px;font-weight:700;color:#303972;">{{ $total }}</p></div>
    </div>
    <div class="ppp-stat-card" style="flex:1;min-width:140px;background:#fff;border-radius:12px;padding:20px;box-shadow:0 0 30px rgba(0,0,0,0.05);">
        <div style="width:50px;height:50px;border-radius:50%;background:rgba(30,186,98,0.1);color:#1EBA62;display:flex;align-items:center;justify-content:center;font-size:24px;"><i class="fa fa-wifi"></i></div>
        <div style="margin-left:15px;"><h4 style="margin:0;font-size:14px;color:#A098AE;">Online</h4><p style="margin:5px 0 0;font-size:28px;font-weight:700;color:#303972;">{{ $online }}</p></div>
    </div>
    <div class="ppp-stat-card" style="flex:1;min-width:140px;background:#fff;border-radius:12px;padding:20px;box-shadow:0 0 30px rgba(0,0,0,0.05);">
        <div style="width:50px;height:50px;border-radius:50%;background:rgba(253,83,83,0.1);color:#fd5353;display:flex;align-items:center;justify-content:center;font-size:24px;"><i class="fa fa-power-off"></i></div>
        <div style="margin-left:15px;"><h4 style="margin:0;font-size:14px;color:#A098AE;">Offline</h4><p style="margin:5px 0 0;font-size:28px;font-weight:700;color:#303972;">{{ $offline }}</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-users"></i> PPP Secrets
            <span style="float:right;">
                <button type="button" class="btn bg-success text-white" onclick="openAddModal()"><i class="fa fa-plus"></i> Tambah PPP Secret</button>
            </span>
        </h3>
    </div>
    <div class="card-body">
        <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
        <div class="overflow box-bordered" style="max-height: 70vh;">
            <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Profile</th>
                        <th>Local Address</th>
                        <th>Remote Address</th>
                        <th>Status</th>
                        <th>Comment</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secrets as $i => $s)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $s['name'] ?? '-' }}</td>
                        <td>{{ $s['service'] ?? '-' }}</td>
                        <td>{{ $s['profile'] ?? '-' }}</td>
                        <td>{{ $s['local-address'] ?? '-' }}</td>
                        <td>{{ $s['remote-address'] ?? '-' }}</td>
                        <td>
                            @if(!empty($onlineUsers[$s['name'] ?? '']))
                            <span class="badge" style="background:linear-gradient(135deg,#1EBA62,#17a34a);color:#fff;">Online</span>
                            @else
                            <span class="badge" style="background:#E0E0E0;color:#888;">Offline</span>
                            @endif
                        </td>
                        <td>{{ $s['comment'] ?? '-' }}</td>
                        <td>
                            @if(!empty($s['.id']))
                                <button type="button"
                                        class="btn btn-xs btn-info text-white"
                                        title="Edit"
                                        data-id="{{ $s['.id'] ?? '' }}"
                                        data-name="{{ $s['name'] ?? '' }}"
                                        data-service="{{ $s['service'] ?? 'any' }}"
                                        data-profile="{{ $s['profile'] ?? '' }}"
                                        data-local-address="{{ $s['local-address'] ?? '' }}"
                                        data-remote-address="{{ $s['remote-address'] ?? '' }}"
                                        data-comment="{{ $s['comment'] ?? '' }}"
                                        onclick="openEditModalFromButton(this)">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-xs btn-danger"
                                        title="Hapus"
                                        data-id="{{ $s['.id'] ?? '' }}"
                                        data-name="{{ $s['name'] ?? '' }}"
                                        onclick="openDeleteModalFromButton(this)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">Tidak ada PPP secret.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: Add Secret --}}
<div class="ppp-modal-backdrop" id="addSecretModal" onclick="closeModalByBackdrop(event, 'addSecretModal')">
    <div class="ppp-modal ppp-modal-modern">
        <div class="ppp-modal-header">
            <h4><i class="fa fa-plus"></i> Tambah PPP Secret</h4>
            <button type="button" class="ppp-modal-close" onclick="closeModal('addSecretModal')">&times;</button>
        </div>
        <div class="ppp-modal-body">
            <form method="POST" action="{{ route('ppp.store-secret') }}">
                @csrf
                <div class="secret-form-grid">
                    <div class="secret-field">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="contoh: pelanggan01">
                    </div>
                    <div class="secret-field">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" required placeholder="isi password PPP">
                    </div>

                    <div class="secret-field">
                        <label class="form-label">Service</label>
                        <select name="service" class="form-control">
                            <option value="any">any</option>
                            <option value="pppoe">pppoe</option>
                            <option value="pptp">pptp</option>
                            <option value="l2tp">l2tp</option>
                            <option value="sstp">sstp</option>
                            <option value="ovpn">ovpn</option>
                            <option value="async">async</option>
                        </select>
                    </div>
                    <div class="secret-field">
                        <label class="form-label">Profile</label>
                        <select name="profile" class="form-control" required>
                            @foreach($profiles as $p)
                                <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="secret-field">
                        <label class="form-label">Local Address</label>
                        <input type="text" name="local-address" class="form-control" placeholder="opsional">
                    </div>
                    <div class="secret-field">
                        <label class="form-label">Remote Address</label>
                        <input type="text" name="remote-address" class="form-control" placeholder="opsional">
                    </div>

                    <div class="secret-field full">
                        <label class="form-label">Comment</label>
                        <input type="text" name="comment" class="form-control" placeholder="catatan tambahan (opsional)">
                    </div>
                </div>
                <div class="secret-form-actions">
                    <button type="button" class="secret-btn secondary" onclick="closeModal('addSecretModal')"><i class="fa fa-times"></i> Batal</button>
                    <button type="submit" class="secret-btn primary"><i class="fa fa-save"></i> Simpan Secret</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Edit Secret --}}
<div class="ppp-modal-backdrop" id="editSecretModal" onclick="closeModalByBackdrop(event, 'editSecretModal')">
    <div class="ppp-modal">
        <div class="ppp-modal-header">
            <h4 style="margin:0;"><i class="fa fa-edit"></i> Edit PPP Secret</h4>
            <button type="button" class="ppp-modal-close" onclick="closeModal('editSecretModal')">&times;</button>
        </div>
        <div class="ppp-modal-body">
            <form method="POST" id="editSecretForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Name</b></label>
                            <input type="text" id="editSecretName" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Password</b> <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                            <input type="text" name="password" class="form-control" value="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Service</b></label>
                            <select name="service" id="editSecretService" class="form-control">
                                <option value="any">any</option>
                                <option value="pppoe">pppoe</option>
                                <option value="pptp">pptp</option>
                                <option value="l2tp">l2tp</option>
                                <option value="sstp">sstp</option>
                                <option value="ovpn">ovpn</option>
                                <option value="async">async</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Profile</b></label>
                            <select name="profile" id="editSecretProfile" class="form-control" required>
                                @foreach($profiles as $p)
                                    <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Local Address</b></label>
                            <input type="text" name="local-address" id="editSecretLocalAddress" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><b>Remote Address</b></label>
                            <input type="text" name="remote-address" id="editSecretRemoteAddress" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><b>Comment</b></label>
                    <input type="text" name="comment" id="editSecretComment" class="form-control" placeholder="Optional">
                </div>
                <button type="submit" class="btn bg-success text-white"><i class="fa fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn bg-secondary text-white" onclick="closeModal('editSecretModal')"><i class="fa fa-times"></i> Batal</button>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Delete Secret --}}
<div class="ppp-modal-backdrop" id="deleteSecretModal" onclick="closeModalByBackdrop(event, 'deleteSecretModal')">
    <div class="ppp-modal" style="max-width:520px;">
        <div class="ppp-modal-header">
            <h4 style="margin:0;color:#b91c1c;"><i class="fa fa-trash"></i> Hapus PPP Secret</h4>
            <button type="button" class="ppp-modal-close" onclick="closeModal('deleteSecretModal')">&times;</button>
        </div>
        <div class="ppp-modal-body">
            <p style="margin-bottom:16px;">Yakin ingin menghapus secret: <strong id="deleteSecretName">-</strong> ?</p>
            <form method="POST" id="deleteSecretForm">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> Ya, Hapus</button>
                <button type="button" class="btn bg-secondary text-white" onclick="closeModal('deleteSecretModal')"><i class="fa fa-times"></i> Batal</button>
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

function openModal(id) {
    document.getElementById(id)?.classList.add('show');
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('show');
}
function closeModalByBackdrop(event, id) {
    if (event.target && event.target.id === id) {
        closeModal(id);
    }
}

function openAddModal() {
    openModal('addSecretModal');
}

function openEditModal(item) {
    const form = document.getElementById('editSecretForm');
    form.action = "{{ url('/ppp/secrets') }}/" + encodeURIComponent(item.id || '');
    document.getElementById('editSecretName').value = item.name || '';
    document.getElementById('editSecretService').value = item.service || 'any';
    document.getElementById('editSecretProfile').value = item.profile || '';
    document.getElementById('editSecretLocalAddress').value = item.localAddress || '';
    document.getElementById('editSecretRemoteAddress').value = item.remoteAddress || '';
    document.getElementById('editSecretComment').value = item.comment || '';
    openModal('editSecretModal');
}

function openEditModalFromButton(button) {
    const item = {
        id: button?.dataset?.id || '',
        name: button?.dataset?.name || '',
        service: button?.dataset?.service || 'any',
        profile: button?.dataset?.profile || '',
        localAddress: button?.dataset?.localAddress || '',
        remoteAddress: button?.dataset?.remoteAddress || '',
        comment: button?.dataset?.comment || '',
    };
    openEditModal(item);
}

function openDeleteModal(item) {
    const form = document.getElementById('deleteSecretForm');
    form.action = "{{ url('/ppp/secrets') }}/" + encodeURIComponent(item.id || '');
    document.getElementById('deleteSecretName').textContent = item.name || '-';
    openModal('deleteSecretModal');
}

function openDeleteModalFromButton(button) {
    const item = {
        id: button?.dataset?.id || '',
        name: button?.dataset?.name || '',
    };
    openDeleteModal(item);
}
</script>
@endpush
@endsection
