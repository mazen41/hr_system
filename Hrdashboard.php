
<?php
$screen = 'الحضور والانصراف';
$page_title = 'الحضور والانصراف';

include_once('inc/header.php');

// get lat and lng for branch location
$query_loc = "SELECT TypeBracnhLocation, Onepoint, MorePoint FROM branches WHERE branch_id = :id LIMIT 1";
$st_loc = $connect_pdo->prepare($query_loc);
$st_loc->execute(array(':id' => $branch));
if ($st_loc->rowCount() > 0) {
    $row_loc = $st_loc->fetch();
} else {
    $row_loc = ['TypeBracnhLocation' => '', 'Onepoint' => '', 'MorePoint' => ''];
}

// Get today's attendance for current user
$today_att = [];
$att_q = $connect_pdo->prepare("SELECT Type, Time FROM tblattendance WHERE EmpID = :uid AND Date = :d ORDER BY AttendanceID ASC");
$att_q->execute([':uid' => $user, ':d' => date('Y-m-d')]);
$today_att = $att_q->fetchAll(PDO::FETCH_ASSOC);
$checkedIn = false;
$lastAction = null;
foreach ($today_att as $att) {
    $lastAction = $att;
    if ($att['Type'] == 1) $checkedIn = true;
    if ($att['Type'] == 2) $checkedIn = false;
}
$currentTime = date('H:i');
$currentDate = date('Y-m-d');
$dayName = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'][date('w')];
?>

<style>
.att-page { padding: 20px 0; }
.att-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    padding: 32px 24px;
    max-width: 480px;
    margin: 0 auto;
}
.att-greeting {
    font-size: 26px;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 4px;
}
.att-date {
    font-size: 16px;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 24px;
}
.att-status {
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
}
.att-status.inside {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border: 2px solid #6ee7b7;
}
.att-status.outside {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    border: 2px solid #fca5a5;
}
.att-status.loading {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #3730a3;
    border: 2px solid #a5b4fc;
}
.att-status.no-location {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border: 2px solid #fcd34d;
}
.att-status-icon { font-size: 36px; display: block; margin-bottom: 8px; }
.att-status-text { font-size: 18px; line-height: 1.6; }

.att-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    width: 100%;
    padding: 20px 24px !important;
    font-size: 22px !important;
    font-weight: 800 !important;
    border-radius: 16px !important;
    border: none !important;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 70px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.att-btn:active { transform: scale(0.97); }
.att-btn-checkin {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
    color: #fff !important;
}
.att-btn-checkin:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8) !important; box-shadow: 0 6px 24px rgba(37,99,235,0.4); }
.att-btn-checkout {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    color: #fff !important;
}
.att-btn-checkout:hover { background: linear-gradient(135deg, #dc2626, #b91c1c) !important; box-shadow: 0 6px 24px rgba(220,38,38,0.4); }
.att-btn i { font-size: 28px; }

.att-timeline { margin-top: 28px; }
.att-timeline-title {
    font-size: 16px;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f3f4f6;
}
.att-timeline-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid #f9fafb;
}
.att-timeline-dot {
    width: 14px; height: 14px;
    border-radius: 50%;
    flex-shrink: 0;
}
.att-timeline-dot.in { background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,0.2); }
.att-timeline-dot.out { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.2); }
.att-timeline-label { font-size: 16px; font-weight: 600; color: #374151; }
.att-timeline-time { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-right: auto; direction: ltr; }

.att-clock {
    font-size: 48px;
    font-weight: 800;
    color: #1a1a2e;
    text-align: center;
    margin-bottom: 8px;
    direction: ltr;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 575.98px) {
    .att-card { padding: 24px 16px; border-radius: 16px; }
    .att-greeting { font-size: 22px; }
    .att-clock { font-size: 40px; }
    .att-btn { font-size: 20px !important; padding: 18px 20px !important; min-height: 64px; }
}
</style>

<section class="content att-page">
    <div class="container-fluid">
        <div class="row">
            
            <!-- Right Column: Action & Status -->
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="att-card h-100">
                    <!-- Clock -->
                    <div class="att-clock" id="liveClock"><?= $currentTime ?></div>
                    
                    <!-- Greeting -->
                    <div class="text-center">
                        <div class="att-greeting">مرحباً <?= $userName ?></div>
                        <div class="att-date">
                            <i class="far fa-calendar-alt"></i>
                            <?= $dayName ?> ، <?= $currentDate ?>
                        </div>
                    </div>

                    <!-- GPS Status -->
                    <div id="GPSMES" class="att-status loading">
                        <span class="att-status-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <span class="att-status-text">جاري تحديد موقعك...</span>
                    </div>

                    <!-- Attendance Button -->
                    <div id="ConOFInOut">
                        <?php if ($checkedIn): ?>
                        <button id="showAttendanceSystem" class="att-btn att-btn-checkout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>تسجيل الانصراف</span>
                        </button>
                        <?php else: ?>
                        <button id="showAttendanceSystem" class="att-btn att-btn-checkin">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>تسجيل الحضور</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Left Column: Timeline -->
            <div class="col-lg-7 col-md-12 mb-4">
                <div class="att-card h-100">
                    <div class="att-timeline-title">
                        <i class="far fa-clock"></i> سجل اليوم
                    </div>
                    
                    <?php if (!empty($today_att)): ?>
                    <div class="att-timeline">
                        <?php foreach ($today_att as $att): ?>
                        <div class="att-timeline-item">
                            <span class="att-timeline-dot <?= $att['Type'] == 1 ? 'in' : 'out' ?>"></span>
                            <div class="d-flex flex-column flex-grow-1">
                                <span class="att-timeline-label"><?= $att['Type'] == 1 ? 'تسجيل دخول' : 'تسجيل خروج' ?></span>
                                <small class="text-muted">تم التسجيل عبر التطبيق</small>
                            </div>
                            <span class="att-timeline-time"><?= date('h:i A', strtotime($att['Time'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-history fa-3x mb-3" style="opacity:0.3"></i>
                        <p>لا يوجد سجل حضور لهذا اليوم</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function () {

    // Live clock
    function updateClock() {
        var now = new Date();
        var h = now.getHours().toString().padStart(2,'0');
        var m = now.getMinutes().toString().padStart(2,'0');
        var s = now.getSeconds().toString().padStart(2,'0');
        $('#liveClock').text(h + ':' + m + ':' + s);
    }
    setInterval(updateClock, 1000);
    updateClock();


//   for one minte
    const typelocation="<?php echo $row_loc['TypeBracnhLocation'] ?>";
    if(typelocation=="")
    {
        $("#GPSMES").removeClass('loading').addClass('no-location').html('<span class="att-status-icon"><i class="fas fa-exclamation-triangle"></i></span><span class="att-status-text">لم يتم تحديد موقع الفرع بعد</span>');
        $("#ConOFInOut").html("");
        return;
    }
 if(typelocation==1){
const onepoint = <?php echo json_encode($row_loc['Onepoint']); ?>;
const parts = onepoint.split(',');
const allowedLatitude = parseFloat(parts[0]);  
const allowedLongitude = parseFloat(parts[1]);    
const allowedRadius = parseInt(parts[2])+10000;

whenload = function () {
        checkLocationPermission(allowedLatitude, allowedLongitude, allowedRadius);
    };
}

    function getDistanceFromLatLonInMeters(lat1, lon1, lat2, lon2) {
        const radLat1 = (Math.PI / 180) * lat1;
        const radLon1 = (Math.PI / 180) * lon1;
        const radLat2 = (Math.PI / 180) * lat2;
        const radLon2 = (Math.PI / 180) * lon2;

        const dLat = radLat2 - radLat1;
        const dLon = radLon2 - radLon1;

        const a =
            Math.sin(dLat / 2) ** 2 +
            Math.cos(radLat1) * Math.cos(radLat2) * Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        const radius = 6371 * 1000; // 6,371,000 متر
        const distance = radius * c;

        return Math.round(distance);
    }



    function checkLocationPermission(dblat,dblon,distince_db) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    var lat = position.coords.latitude;
                    var lon = position.coords.longitude;

                    const distance = getDistanceFromLatLonInMeters(lat, lon, dblat, dblon);
                    if (distance <= distince_db) {
                        $("#GPSMES").removeClass('loading').addClass('inside').html('<span class="att-status-icon"><i class="fas fa-check-circle"></i></span><span class="att-status-text">أنت داخل نطاق الشركة</span>');
                    } else {
                        $("#GPSMES").removeClass('loading').addClass('outside').html('<span class="att-status-icon"><i class="fas fa-times-circle"></i></span><span class="att-status-text">أنت خارج نطاق الشركة</span>');
                        $("#ConOFInOut").html("");
                    }
                },
                function (error) {
                    var errMsg = 'حدث خطأ غير متوقع';
                    if (error.code === error.PERMISSION_DENIED) {
                        errMsg = 'يرجى السماح بالوصول للموقع الجغرافي من إعدادات المتصفح';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errMsg = 'تعذر الوصول إلى الموقع الجغرافي';
                    } else if (error.code === error.TIMEOUT) {
                        errMsg = 'انتهت مهلة تحديد الموقع، حاول مرة أخرى';
                    }
                    $("#GPSMES").removeClass('loading').addClass('no-location').html('<span class="att-status-icon"><i class="fas fa-exclamation-triangle"></i></span><span class="att-status-text">' + errMsg + '</span>');
                    $("#ConOFInOut").html("");
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0
                }
            );
        } else {
            $("#GPSMES").removeClass('loading').addClass('no-location').html('<span class="att-status-icon"><i class="fas fa-map-marked-alt"></i></span><span class="att-status-text">جهازك لا يدعم تحديد المواقع</span>');
            $("#ConOFInOut").html("");
        }
    }

//   اربع نقاط
if(typelocation==2)
{
const MorePoint = <?php echo json_encode($row_loc['MorePoint']); ?>;
const parts = MorePoint.split(',');
const separatedPoints = parts.map(point => point.split('-').map(val => parseFloat(val)));

    // حق اربع نقاط
    whenload = function () {
        checkLocationPermissionfourpoint(separatedPoints);
    };    
}
function isPointInPolygon(point, polygon) {
    let x = point.lat, y = point.lon;
    let inside = false;

    for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
        let xi = polygon[i].lat, yi = polygon[i].lon;
        let xj = polygon[j].lat, yj = polygon[j].lon;

        let intersect = ((yi > y) !== (yj > y)) &&
                        (x < (xj - xi) * (y - yi) / (yj - yi + 0.0000001) + xi);
        if (intersect) inside = !inside;
    }

    return inside;
}
function checkLocationPermissionfourpoint(allpoint) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                const isInside = isPointInPolygon({ lat: lat, lon: lon }, allpoint);

                if (isInside) {
                    $("#GPSMES").removeClass('loading').addClass('inside').html('<span class="att-status-icon"><i class="fas fa-check-circle"></i></span><span class="att-status-text">أنت داخل نطاق الشركة</span>');
                } else {
                    $("#GPSMES").removeClass('loading').addClass('outside').html('<span class="att-status-icon"><i class="fas fa-times-circle"></i></span><span class="att-status-text">أنت خارج نطاق الشركة</span>');
                    $("#ConOFInOut").html("");
                }
            },
            function (error) {
                var errMsg2 = 'حدث خطأ غير متوقع';
                if (error.code === error.PERMISSION_DENIED) {
                    errMsg2 = 'يرجى السماح بالوصول للموقع الجغرافي من إعدادات المتصفح';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errMsg2 = 'تعذر الوصول إلى الموقع الجغرافي';
                } else if (error.code === error.TIMEOUT) {
                    errMsg2 = 'انتهت مهلة تحديد الموقع، حاول مرة أخرى';
                }
                $("#GPSMES").removeClass('loading').addClass('no-location').html('<span class="att-status-icon"><i class="fas fa-exclamation-triangle"></i></span><span class="att-status-text">' + errMsg2 + '</span>');
                $("#ConOFInOut").html("");
            },
            {
                enableHighAccuracy: true,
                maximumAge: 0
            }
        );
    } else {
        $("#GPSMES").removeClass('loading').addClass('no-location').html('<span class="att-status-icon"><i class="fas fa-map-marked-alt"></i></span><span class="att-status-text">جهازك لا يدعم تحديد المواقع</span>');
        $("#ConOFInOut").html("");
    }
}
// add to table
$('#showAttendanceSystem').on('click', function(e){  
	e.preventDefault();
    SendAttench()	
});

function SendAttench()
{
    var $btn = $('#showAttendanceSystem');
    $btn.prop('disabled', true).css('opacity','0.7');
    $.ajax({
	type: 'POST',
	url: "./hr-app/Hrdashboard",
	dataType: "json",
	success: function (data) {
		if (data.result) {
			toastr.success(data.msg);
			setTimeout(function(){ location.reload(); }, 1200);
		} else {
			toastr.error(data.msg);
			$btn.prop('disabled', false).css('opacity','1');
		}
	},
	error: function(){
		toastr.error('حدث خطأ في الاتصال');
		$btn.prop('disabled', false).css('opacity','1');
	}
});
}

    window.onload = whenload;
});
</script>
