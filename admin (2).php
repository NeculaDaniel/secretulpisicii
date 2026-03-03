<?php
session_start();

// 1. SECURITATE
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';

// Includem funcțiile doar dacă există
if (file_exists(__DIR__ . '/oblio_functions.php')) require_once __DIR__ . '/oblio_functions.php';
if (file_exists(__DIR__ . '/ecolet_functions.php')) require_once __DIR__ . '/ecolet_functions.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}

// 2. ACTIUNI BACKEND (AJAX)
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Actiune necunoscuta'];

    try {
        if ($_POST['action'] == 'update_order') {
            // Editare comanda
            $sql = "UPDATE orders SET full_name=?, phone=?, email=?, city=?, county=?, address_line=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['full_name'], $_POST['phone'], $_POST['email'], 
                $_POST['city'], $_POST['county'], $_POST['address_line'], 
                $_POST['order_id']
            ]);
            $response = ['success' => true];
        }
        elseif ($_POST['action'] == 'generate_invoice') {
            // Generare Factura Oblio
            if (!function_exists('sendToOblio')) throw new Exception("Functia Oblio lipseste.");
            $msg = sendToOblio($_POST['order_id']);
            $response = ['success' => true, 'message' => $msg];
        }
        elseif ($_POST['action'] == 'generate_awb') {
            // Generare AWB Ecolet
            if (!function_exists('generateEcoletAWB')) throw new Exception("Functia Ecolet lipseste.");
            $msg = generateEcoletAWB($_POST['order_id']);
            $response = ['success' => true, 'message' => $msg];
        }
        elseif ($_POST['action'] == 'get_settings') {
            // Identic oclar GET /api/admin/settings
            $settings = getAllAutomationSettings();
            $response = ['success' => true, 'settings' => $settings];
        }
        elseif ($_POST['action'] == 'update_setting') {
            // Identic oclar POST /api/admin/settings — UPSERT
            $key   = $_POST['key']   ?? '';
            $value = ($_POST['value'] === 'true' || $_POST['value'] === '1');
            updateAutomationSetting($key, $value);
            $response = ['success' => true];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// 3. AFISARE TABEL
$sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 100";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Statistici
$pendingInv = 0;
$pendingAWB = 0;
foreach($orders as $o) {
    if ($o['oblio_status'] == 0) $pendingInv++;
    if (empty($o['awb_number'])) $pendingAWB++;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Secretul Pisicii</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: #f3f4f6; font-family: sans-serif; padding-bottom: 100px; }
        .slide-up { transform: translateY(0); }
        .slide-down { transform: translateY(150%); }
        .selected-row { background-color: #e0f2fe !important; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-green { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-red { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-blue { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>

<div class="max-w-[95%] mx-auto p-4">
    <div class="bg-white p-4 rounded-xl shadow mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Comenzi</h1>
            <div class="text-xs text-gray-500 flex gap-3 mt-1">
                <span>Fără Factură: <b class="text-red-500"><?php echo $pendingInv; ?></b></span>
                <span>Fără AWB: <b class="text-orange-500"><?php echo $pendingAWB; ?></b></span>
            </div>
        </div>
        <a href="logout.php" class="text-red-500 hover:underline text-sm">Deconectare</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead class="bg-gray-800 text-white text-xs uppercase">
                <tr>
                    <th class="p-3 w-10 text-center"><input type="checkbox" id="selectAll"></th>
                    <th class="p-3">ID / Data</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Livrare</th>
                    <th class="p-3">Total</th>
                    <th class="p-3 text-center">Factură</th>
                    <th class="p-3 text-center">AWB</th>
                    <th class="p-3 text-right">Acțiuni</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                <?php foreach($orders as $o): ?>
                    <tr class="hover:bg-gray-50 transition cursor-pointer group" onclick="toggleRow(this)">
                        <td class="p-3 text-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="order-check" value="<?php echo $o['id']; ?>">
                        </td>
                        
                        <td class="p-3">
                            <div class="font-bold text-gray-700">#<?php echo $o['id']; ?></div>
                            <div class="text-[10px] text-gray-400"><?php echo date('d.m H:i', strtotime($o['created_at'])); ?></div>
                        </td>

                        <td class="p-3">
                            <div class="font-bold text-gray-800"><?php echo $o['full_name']; ?></div>
                            <div class="text-xs text-blue-600"><?php echo $o['phone']; ?></div>
                        </td>

                        <td class="p-3">
                            <div class="text-xs font-semibold">
                                <?php echo ($o['shipping_method'] == 'easybox') ? '📦 EasyBox' : '🚚 Curier'; ?>
                            </div>
                            <div class="text-xs text-gray-500 truncate max-w-[150px]" title="<?php echo $o['city']; ?>">
                                <?php echo $o['city']; ?>, <?php echo $o['county']; ?>
                            </div>
                        </td>

                        <td class="p-3 font-bold">
                            <?php echo $o['total_price']; ?> Lei
                            <div class="text-[9px] text-gray-400 uppercase"><?php echo $o['payment_method']; ?></div>
                        </td>

                        <td class="p-3 text-center" id="inv-<?php echo $o['id']; ?>">
                            <?php if($o['oblio_status'] == 1): ?>
                                <a href="<?php echo $o['oblio_link']; ?>" target="_blank" class="badge badge-green hover:underline">Vezi PDF</a>
                            <?php else: ?>
                                <span class="badge badge-red">Lipsă</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-3 text-center" id="awb-<?php echo $o['id']; ?>">
                            <?php if(!empty($o['awb_number'])): ?>
                                <span class="badge badge-blue"><?php echo $o['awb_number']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-red">Lipsă</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-3 text-right" onclick="event.stopPropagation()">
                            <div class="flex justify-end gap-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition">
                                <button onclick='openEdit(<?php echo json_encode($o); ?>)' class="p-1 text-gray-400 hover:text-blue-600 border rounded">✏️</button>
                                
                                <?php if($o['oblio_status'] == 0): ?>
                                    <button onclick="doAction(<?php echo $o['id']; ?>, 'generate_invoice')" class="px-2 py-1 text-xs bg-green-50 text-green-700 border border-green-200 rounded hover:bg-green-100">+F</button>
                                <?php endif; ?>

                                <?php if(empty($o['awb_number'])): ?>
                                    <button onclick="doAction(<?php echo $o['id']; ?>, 'generate_awb')" class="px-2 py-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100">+A</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="actionBar" class="fixed bottom-0 left-0 w-full bg-white border-t p-4 shadow-2xl transform slide-down transition-transform duration-300 flex justify-between items-center md:px-20 z-50">
    <div class="font-bold text-gray-700">Selectate: <span id="selCount" class="text-blue-600">0</span></div>
    <div class="flex gap-2">
        <button onclick="processBulk('generate_invoice')" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-green-700">Generează Facturi</button>
        <button onclick="processBulk('generate_awb')" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-blue-700">Generează AWB</button>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold mb-4">Editare Comandă</h2>
        <form id="editForm" class="space-y-3">
            <input type="hidden" name="action" value="update_order">
            <input type="hidden" name="order_id" id="e_id">
            <input class="w-full border p-2 rounded" name="full_name" id="e_name" placeholder="Nume">
            <div class="flex gap-2">
                <input class="w-1/2 border p-2 rounded" name="phone" id="e_phone" placeholder="Tel">
                <input class="w-1/2 border p-2 rounded" name="email" id="e_email" placeholder="Email">
            </div>
            <div class="flex gap-2">
                <input class="w-1/2 border p-2 rounded" name="county" id="e_county" placeholder="Județ">
                <input class="w-1/2 border p-2 rounded" name="city" id="e_city" placeholder="Oraș">
            </div>
            <textarea class="w-full border p-2 rounded" name="address_line" id="e_addr" rows="2" placeholder="Adresă"></textarea>
            <div class="flex gap-2 mt-4">
                <button type="button" onclick="$('#editModal').addClass('hidden')" class="flex-1 bg-gray-200 py-2 rounded">Anulează</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded">Salvează</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. SELECTIE
    function toggleRow(row) {
        let chk = $(row).find('.order-check');
        chk.prop('checked', !chk.prop('checked'));
        chk.prop('checked') ? $(row).addClass('selected-row') : $(row).removeClass('selected-row');
        updateBar();
    }
    $('#selectAll').change(function() {
        let s = this.checked;
        $('.order-check').prop('checked', s);
        s ? $('tbody tr').addClass('selected-row') : $('tbody tr').removeClass('selected-row');
        updateBar();
    });
    function updateBar() {
        let c = $('.order-check:checked').length;
        $('#selCount').text(c);
        c > 0 ? $('#actionBar').removeClass('slide-down') : $('#actionBar').addClass('slide-down');
    }

    // 2. ACTIUNI SINGLE
    function doAction(id, act) {
        let btn = $(event.target);
        let old = btn.text();
        btn.text('...').prop('disabled', true);

        $.post('admin.php', { action: act, order_id: id }, function(res) {
            if(res.success) {
                btn.replaceWith('<span class="text-xs text-green-600 font-bold">OK</span>');
                if(act == 'generate_invoice') $('#inv-'+id).html('<span class="badge badge-green">Emisă</span>');
                if(act == 'generate_awb') $('#awb-'+id).html('<span class="badge badge-blue">Generat</span>');
            } else {
                alert('Eroare: ' + res.message);
                btn.text(old).prop('disabled', false);
            }
        }, 'json');
    }

    // 3. BULK
    async function processBulk(act) {
        let sel = $('.order-check:checked');
        if(!sel.length || !confirm('Sigur?')) return;

        for(let i=0; i<sel.length; i++) {
            let id = $(sel[i]).val();
            // Verificare sumară să nu refacem ce e gata
            let row = $(sel[i]).closest('tr');
            if(act == 'generate_invoice' && row.find('#inv-'+id).text().trim() != 'Lipsă') continue;
            if(act == 'generate_awb' && row.find('#awb-'+id).text().trim() != 'Lipsă') continue;

            await new Promise(r => {
                $.post('admin.php', { action: act, order_id: id }, function(res) {
                    if(res.success) {
                        if(act == 'generate_invoice') row.find('#inv-'+id).html('<span class="badge badge-green">OK</span>');
                        if(act == 'generate_awb') row.find('#awb-'+id).html('<span class="badge badge-blue">OK</span>');
                    }
                    r();
                }, 'json');
            });
        }
        alert('Gata!');
        location.reload();
    }

    // 4. EDIT
    function openEdit(o) {
        $('#e_id').val(o.id);
        $('#e_name').val(o.full_name);
        $('#e_phone').val(o.phone);
        $('#e_email').val(o.email);
        $('#e_county').val(o.county);
        $('#e_city').val(o.city);
        $('#e_addr').val(o.address_line);
        $('#editModal').removeClass('hidden');
    }
    $('#editForm').submit(function(e) {
        e.preventDefault();
        $.post('admin.php', $(this).serialize(), function(res) {
            if(res.success) location.reload();
            else alert(res.message);
        }, 'json');
    });

    // AUTOMATIZARE — identic oclar GET/POST /api/admin/settings
    function loadSettings() {
        $.post('admin.php', {action:'get_settings'}, function(res) {
            if (!res.success) return;
            applySw('sw_automation_enabled', res.settings.automation_enabled);
            applySw('sw_auto_oblio',         res.settings.auto_oblio);
            applySw('sw_auto_ecolet',        res.settings.auto_ecolet);
            updateAutobadge(res.settings.automation_enabled);
        }, 'json');
    }
    function applySw(id, val) {
        var cb = document.getElementById(id);
        if (!cb) return;
        cb.checked = !!val;
        var lbl   = cb.closest('label') || cb.parentElement.parentElement;
        var track = lbl.querySelector('.sw-track');
        var thumb = lbl.querySelector('.sw-thumb');
        if (track) track.style.background = val ? '#10b981' : '';
        if (thumb) thumb.style.transform  = val ? 'translateX(20px)' : '';
    }
    function updateAutobage(en) { updateAutobage_inner(en); }
    function updateAutobage_inner(en) {
        var b = document.getElementById('autoStatusBadge');
        if (!b) return;
        b.textContent   = en ? 'ACTIV' : 'OPRIT';
        b.className     = en ? 'text-xs px-2 py-1 rounded font-bold bg-green-100 text-green-700'
                             : 'text-xs px-2 py-1 rounded font-bold bg-red-100 text-red-600';
    }
    function updateAutobage(en) { updateAutobage_inner(en); }
    $('.auto-switch').on('change', function() {
        var key = $(this).data('key');
        var val = this.checked;
        applySw(this.id, val);
        if (key === 'automation_enabled') updateAutobage(val);
        $.post('admin.php', {action:'update_setting', key:key, value: val?'true':'false'}, null, 'json');
    });
    loadSettings();
</script>

</body>
</html>