<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$db = getDB();
$user = currentUser();
$pageTitle = 'Dashboard';

// Stats
$totalPatients = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$todayAppts = $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$checkedIn   = $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status='checked_in'")->fetchColumn();
$totalRevenue= $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();

// Today's appointments
if (isRole('doctor')) {
    $doctorRow = $db->prepare("SELECT id FROM doctors WHERE user_id=?");
    $doctorRow->execute([$user['id']]);
    $docId = $doctorRow->fetchColumn();
    $stmt = $db->prepare("SELECT a.*, p.full_name as patient_name, p.patient_code FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.appointment_date=CURDATE() AND a.doctor_id=? ORDER BY a.appointment_time");
    $stmt->execute([$docId]);
} else {
    $stmt = $db->query("SELECT a.*, p.full_name as patient_name, p.patient_code, u.full_name as doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id JOIN users u ON u.id=d.user_id WHERE a.appointment_date=CURDATE() ORDER BY a.appointment_time");
}
$todayList = $stmt->fetchAll();

// Recent patients (last 5)
$recentPatients = $db->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Good <?= date('H')<12?'morning':(date('H')<17?'afternoon':'evening') ?>, <?= e(explode(' ',$user['name'])[0]) ?></h1>
    <p class="page-subtitle"><?= date('l, d F Y') ?></p>
  </div>
  <?php if(!isRole('doctor')): ?>
  <a href="/mcms/appointments/create.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
    New Appointment
  </a>
  <?php endif; ?>
</div>

<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-label">Total Patients</div>
    <div class="stat-value"><?= number_format($totalPatients) ?></div>
    <div class="stat-sub">Registered in system</div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Today's Appointments</div>
    <div class="stat-value"><?= $todayAppts ?></div>
    <div class="stat-sub"><?= $checkedIn ?> checked in</div>
  </div>
  <div class="stat-card amber">
    <div class="stat-label">This Month Revenue</div>
    <div class="stat-value">₪<?= number_format($totalRevenue, 0) ?></div>
    <div class="stat-sub"><?= date('F Y') ?></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Checked In Now</div>
    <div class="stat-value"><?= $checkedIn ?></div>
    <div class="stat-sub">Awaiting consultation</div>
  </div>
</div>

<div class="dash-grid">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Today's Schedule</span>
      <a href="/mcms/appointments/index.php" class="btn btn-secondary btn-sm">View all</a>
    </div>
    <?php if(empty($todayList)): ?>
    <div class="empty-state"><p>No appointments today.</p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Patient</th><?= !isRole('doctor')?'<th>Doctor</th>':'' ?><th>Reason</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($todayList as $a): ?>
      <tr>
        <td><strong><?= date('g:i A', strtotime($a['appointment_time'])) ?></strong></td>
        <td>
          <div><?= e($a['patient_name']) ?></div>
          <div class="text-muted"><?= e($a['patient_code']) ?></div>
        </td>
        <?php if(!isRole('doctor')): ?><td><?= e($a['doctor_name'] ?? '') ?></td><?php endif; ?>
        <td class="text-muted"><?= e(mb_strimwidth($a['visit_reason'], 0, 30, '…')) ?></td>
        <td><span class="badge badge-<?= $a['status'] ?>"><?= str_replace('_',' ',ucfirst($a['status'])) ?></span></td>
        <td>
          <div class="actions-cell">
            <?php if($a['status']==='scheduled' && !isRole('doctor')): ?>
            <a href="/mcms/appointments/checkin.php?id=<?= $a['id'] ?>" class="btn btn-success btn-sm">Check In</a>
            <?php endif; ?>
            <?php if(($a['status']==='checked_in') && isRole('doctor')): ?>
            <a href="/mcms/doctor/consult.php?appt=<?= $a['id'] ?>" class="btn btn-primary btn-sm">Consult</a>
            <?php endif; ?>
            <a href="/mcms/appointments/view.php?id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">View</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Patients</span>
      <a href="/mcms/patients/index.php" class="btn btn-secondary btn-sm">View all</a>
    </div>
    <?php foreach($recentPatients as $p): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--blue-light);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;color:var(--blue);flex-shrink:0;">
        <?= strtoupper(substr($p['full_name'],0,1)) ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:500;font-size:14px;"><?= e($p['full_name']) ?></div>
        <div class="text-muted"><?= e($p['patient_code']) ?> · <?= e($p['gender']) ?></div>
      </div>
      <a href="/mcms/patients/view.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">View</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
