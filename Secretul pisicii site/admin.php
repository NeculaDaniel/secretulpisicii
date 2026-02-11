<?php
session_start(); // OBLIGATORIU: Trebuie sa fie prima linie

// ==============================
//  VERIFICARE SECURITATE
// ==============================
// FIX: Am sters litera 'A' care cauza eroarea fatala
if (!isset($_SESSION['admin_logged_in']) || 
    $_SESSION['admin_logged_in'] !== true || 
    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    
    session_destroy();
    header('Location: login.php');
    exit;
}

// Includem fisierele necesare
require_once 'db_connect.php';
require_once 'oblio_functions.php';

$pdo = getDbConnection();

// ==============================
//  BACKEND ACTIONS (AJAX)
// ==============================

// Functie helper pentru a trimite JSON curat
function sendJsonResponse($data) {
    // Curatam orice output anterior (spatii, warning-uri, HTML)
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit; // Oprim executia scriptului aici
}

// 1. UPDATE COMANDA (EDIT)
if (isset($_POST['action']) && $_POST['action'] == 'update_order') {
    try {
        $sql = "UPDATE orders SET full_name=?, phone=?, email=?, city=?, county=?, address_line=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['full_name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['city'],
            $_POST['county'],
            $_POST['address_line'],
            $_POST['order_id']
        ]);
        sendJsonResponse(['success' => true]);
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 2. TOGGLE AUTO-SEND
if (isset($_POST['toggle_auto'])) {
    try {
        $newState = $_POST['state'] === 'true' ? '1' : '0';
        $check = $pdo->query("SELECT count(*) FROM settings WHERE setting_key = 'auto_oblio'")->fetchColumn();
        if($check == 0) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_oblio', ?)")->execute([$newState]);
        } else {
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'auto_oblio'")->execute([$newState]);
        }
        sendJsonResponse(['success' => true]);
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 3. EMITERE FACTURA (Aici era problema cu raspunsul JSON)
if (isset($_POST['action']) && $_POST['action'] == 'manual_invoice') {
    $orderId = $_POST['order_id'];
    
    // Verificam daca comanda exista
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(['success' => false, 'message' => 'Comanda nu exista']);
    }

    // Verificam daca e deja emisa (security check)
    if ($order['oblio_status'] == 1) {
        sendJsonResponse(['success' => true, 'message' => 'Deja emisa']);
    }

    // Pregatim datele pentru Oblio
    $orderDataForOblio = [
        'full_name' => $order['full_name'],
        'email' => $order['email'],
        'phone' => $order['phone'],
        'address_line' => $order['address_line'],
        'city' => $order['city'],
        'county' => $order['county'],
        'total_price' => $order['total_price'], 
        'payment_method' => $order['payment_method'],
        'bundle' => $order['bundle']
    ];

    // Apelam functia din oblio_functions.php
    $res = sendOrderToOblio($orderDataForOblio, $orderId, $pdo);
    
    // Trimitem raspunsul curat inapoi la JS
    sendJsonResponse($res);
}

// ==============================
//  AFISARE & FILTRE (HTML)
// ==============================

// Citire stare Auto
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_oblio'");
$res = $stmt->fetch();
$autoEnabled = ($res && $res['setting_value'] == '1');

// Filtre Date
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$whereClause = "";
$params = [];
if ($startDate && $endDate) {
    $whereClause = "WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
    $params = [$startDate, $endDate];
}

// Query Principal
$sql = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT 150";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Statistici rapide
$ordersWithoutInvoice = 0;
foreach($orders as $o) if($o['oblio_status'] == 0) $ordersWithoutInvoice++;
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard V4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { padding-bottom: 100px; }
        .slide-up { transform: translateY(0%); }
        .slide-down { transform: translateY(150%); }
        .selected-row { background-color: #eff6ff !important; }
        .selected-row td:first-child { border-left: 4px solid #2563eb; }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        input:disabled { cursor: not-allowed; opacity: 0.3; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans text-gray-800">

    <div class="max-w-7xl mx-auto p-2 md:p-6">
        
        <div class="bg-white p-4 rounded-xl shadow-sm mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
            
            <div class="flex items-center gap-4 w-full md:w-auto justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Comenzi</h1>
                    <p class="text-xs text-gray-500">Neemise: <b class="text-red-500"><?php echo $ordersWithoutInvoice; ?></b></p>
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1 rounded-lg border">
                    <span class="text-[10px] font-bold uppercase text-gray-400">Auto</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="autoToggle" class="sr-only peer" <?php echo $autoEnabled ? 'checked' : ''; ?>>
                        <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>
            </div>

            <form class="flex gap-2 items-center bg-gray-50 p-2 rounded-lg w-full md:w-auto overflow-x-auto">
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="border rounded p-1 text-sm bg-white">
                <span class="text-gray-400">-</span>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="border rounded p-1 text-sm bg-white">
                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-bold shadow">Caută</button>
                <?php if($startDate): ?>
                    <a href="admin.php" class="text-red-500 text-sm font-bold px-2 hover:underline whitespace-nowrap">Șterge Filtre</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden table-container">
            <table class="w-full text-left border-collapse min-w-[800px]"> 
                <thead class="bg-gray-800 text-white text-sm uppercase">
                    <tr>
                        <th class="p-3 w-10 text-center"><input type="checkbox" id="selectAll" class="cursor-pointer transform scale-125"></th>
                        <th class="p-3">Data / ID</th>
                        <th class="p-3">Client (Nume, Tel, Email)</th>
                        <th class="p-3">Adresa</th>
                        <th class="p-3">Total</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 divide-y divide-gray-100 text-sm">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="7" class="p-8 text-center text-gray-400">Nu sunt comenzi.</td></tr>
                    <?php endif; ?>

                    <?php foreach($orders as $o): ?>
                        <?php $isSent = ($o['oblio_status'] == 1); ?>
                        <tr class="hover:bg-gray-50 transition <?php echo $isSent ? '' : 'cursor-pointer row-clickable'; ?>" 
                            data-id="<?php echo $o['id']; ?>"
                            onclick="toggleRow(this, <?php echo $o['id']; ?>, <?php echo $isSent ? 'true' : 'false'; ?>)">
                            
                            <td class="p-3 text-center" onclick="event.stopPropagation()">
                                <input type="checkbox" class="order-check transform scale-125" 
                                       value="<?php echo $o['id']; ?>"
                                       <?php echo $isSent ? 'disabled' : ''; ?>>
                            </td>

                            <td class="p-3">
                                <div class="font-bold">#<?php echo $o['id']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('d.m.Y', strtotime($o['created_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo date('H:i', strtotime($o['created_at'])); ?></div>
                            </td>

                            <td class="p-3">
                                <div class="font-bold text-gray-900"><?php echo $o['full_name']; ?></div>
                                <div class="text-xs text-blue-600"><?php echo $o['phone']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $o['email']; ?></div>
                            </td>

                            <td class="p-3 max-w-[200px]">
                                <div class="font-semibold text-xs"><?php echo $o['city']; ?>, <?php echo $o['county']; ?></div>
                                <div class="text-xs text-gray-400 truncate"><?php echo $o['address_line']; ?></div>
                            </td>

                            <td class="p-3 font-bold">
                                <?php echo $o['total_price']; ?> RON
                                <div class="text-[10px] font-normal text-gray-400 uppercase"><?php echo $o['payment_method']; ?></div>
                            </td>

                            <td class="p-3" id="status-<?php echo $o['id']; ?>">
                                <?php if($isSent): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold inline-flex items-center gap-1">
                                        ✅ EMISĂ
                                    </span>
                                <?php else: ?>
                                    <span class="bg-red-50 text-red-600 px-2 py-1 rounded text-xs font-bold">
                                        ⏳ PENDING
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 text-right" onclick="event.stopPropagation()">
                                <div class="flex justify-end gap-2">
                                    <?php if(!$isSent): ?>
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($o)); ?>)" 
                                                class="text-gray-500 hover:text-blue-600 bg-gray-100 hover:bg-blue-50 px-2 py-1 rounded border">
                                            ✏️ Edit
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo $o['oblio_link']; ?>" target="_blank" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-bold shadow hover:bg-green-700">
                                            PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="actionBar" class="fixed bottom-6 left-4 right-4 md:left-auto md:right-6 md:w-96 bg-gray-900/95 backdrop-blur text-white p-4 rounded-2xl shadow-2xl transform slide-down transition-transform duration-300 z-40 flex justify-between items-center border border-gray-700">
        <div class="flex flex-col">
            <span class="text-xs text-gray-400 uppercase tracking-wide">Selectate</span>
            <span class="font-bold text-xl"><span id="selectedCount">0</span> <span class="text-sm font-normal text-gray-400">comenzi</span></span>
        </div>
        
        <button onclick="processBulk()" id="btnBulk" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition active:scale-95">
            <span id="bulkIcon" class="text-lg">🚀</span> 
            <span id="bulkText">Trimite Tot</span>
        </button>
    </div>

    <div id="editModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 transform transition-all scale-100">
            <h2 class="text-xl font-bold mb-4 text-gray-800">✏️ Editează Comanda <span id="modalOrderId" class="text-blue-600"></span></h2>
            
            <form id="editForm" class="space-y-3">
                <input type="hidden" id="edit_id" name="order_id">
                <input type="hidden" name="action" value="update_order">
                
                <div>
                    <label class="text-xs font-bold text-gray-500">Nume Client</label>
                    <input type="text" id="edit_name" name="full_name" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-bold text-gray-500">Telefon</label>
                        <input type="text" id="edit_phone" name="phone" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Email</label>
                        <input type="text" id="edit_email" name="email" class="w-full border rounded p-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-bold text-gray-500">Județ</label>
                        <input type="text" id="edit_county" name="county" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Oraș</label>
                        <input type="text" id="edit_city" name="city" class="w-full border rounded p-2">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500">Adresă Stradă</label>
                    <textarea id="edit_address" name="address_line" rows="2" class="w-full border rounded p-2"></textarea>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="$('#editModal').addClass('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded font-bold">Anulează</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700">Salvează</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const actionBar = document.getElementById('actionBar');
        const countSpan = document.getElementById('selectedCount');

        // Helper sigur pentru parsare JSON
        function parseResponse(res) {
            if (typeof res === 'object') return res;
            try {
                return JSON.parse(res);
            } catch (e) {
                console.error("Eroare parsare:", res);
                return { success: false, message: 'Raspuns invalid de la server' };
            }
        }

        // 1. SELECTIE RANDURI
        function toggleRow(row, id, isSent) {
            if(isSent) return;

            let checkbox = $(row).find('.order-check');
            let isChecked = checkbox.prop('checked');
            
            checkbox.prop('checked', !isChecked);
            
            if(!isChecked) {
                $(row).addClass('selected-row');
            } else {
                $(row).removeClass('selected-row');
            }
            updateUI();
        }

        function updateUI() {
            let checked = $('.order-check:checked');
            countSpan.innerText = checked.length;
            
            if(checked.length > 0) {
                actionBar.classList.remove('slide-down');
                actionBar.classList.add('slide-up');
            } else {
                actionBar.classList.add('slide-down');
                actionBar.classList.remove('slide-up');
            }
        }

        // Select All
        $('#selectAll').change(function() {
            let state = this.checked;
            $('.order-check:not(:disabled)').each(function() {
                $(this).prop('checked', state);
                let row = $(this).closest('tr');
                if(state) row.addClass('selected-row');
                else row.removeClass('selected-row');
            });
            updateUI();
        });

        // 2. MODAL EDITARE
        function openEditModal(data) {
            $('#edit_id').val(data.id);
            $('#modalOrderId').text('#' + data.id);
            $('#edit_name').val(data.full_name);
            $('#edit_phone').val(data.phone);
            $('#edit_email').val(data.email);
            $('#edit_county').val(data.county);
            $('#edit_city').val(data.city);
            $('#edit_address').val(data.address_line);
            
            $('#editModal').removeClass('hidden');
        }

        // Submit Edit Form - REPARAT cu parseResponse
        $('#editForm').submit(function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            
            $.post('admin.php', formData, function(res) {
                let data = parseResponse(res);
                if(data.success) {
                    alert('Date actualizate!');
                    location.reload();
                } else {
                    alert('Eroare: ' + data.message);
                }
            });
        });

        // 3. BULK PROCESS - REPARAT cu parseResponse
        async function processBulk() {
            let selected = $('.order-check:checked');
            if(selected.length === 0) return;

            if(!confirm(`Sigur vrei să emiti ${selected.length} facturi?`)) return;

            let btn = document.getElementById('btnBulk');
            let txt = document.getElementById('bulkText');
            let icon = document.getElementById('bulkIcon');
            
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            icon.innerText = '⏳';

            let total = selected.length;
            let success = 0;

            for (let i = 0; i < total; i++) {
                let checkbox = selected[i];
                let row = $(checkbox).closest('tr');
                let id = $(checkbox).val();

                txt.innerText = `Se trimite ${i+1}/${total}...`;

                await new Promise((resolve) => {
                    $.post('admin.php', { action: 'manual_invoice', order_id: id }, function(res) {
                        let data = parseResponse(res);
                        
                        if(data.success) {
                            success++;
                            row.removeClass('selected-row').addClass('bg-green-50');
                            row.find('td[id^="status-"]').html('✅ OK');
                            $(checkbox).prop('checked', false).prop('disabled', true);
                            row.removeAttr('onclick').removeClass('cursor-pointer');
                        } else {
                            row.addClass('bg-red-50');
                            console.error("Eroare Oblio ID " + id + ": " + data.message);
                        }
                        resolve();
                    }).fail(function() {
                        row.addClass('bg-red-50');
                        resolve();
                    });
                });
            }

            txt.innerText = 'Gata! Refreshing...';
            setTimeout(() => { location.reload(); }, 1000);
        }

        // 4. AUTO TOGGLE
        $('#autoToggle').change(function() {
            $.post('admin.php', { toggle_auto: true, state: $(this).is(':checked') });
        });
    </script>
</body>
</html>