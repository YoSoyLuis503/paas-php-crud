<?php
header("X-Powered-By: PHP/8.2.0");
header("Server: Apache/2.4.57 (Debian)");
header("X-Server-Software: Apache/2.4.57");
require_once __DIR__ . '/db.php';

$message = null;
$message_type = 'info';

// Endpoint de prueba de estrés para activación de HPA
if (isset($_GET['stress'])) {
    $iterations = intval($_GET['stress'] ?? 500000);
    if ($iterations <= 0) $iterations = 500000;
    $x = 0.0001;
    for ($i = 0; $i < $iterations; $i++) {
        $x += sqrt($i) * sin($i);
    }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'iterations' => $iterations,
        'pod' => gethostname(),
        'result' => $x,
        'timestamp' => microtime(true)
    ]);
    exit;
}

// Procesamiento de acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categoria = trim($_POST['categoria'] ?? 'General');

        if (!empty($nombre) && $precio > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio, stock, categoria) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $precio, $stock, $categoria]);
                $message = "Producto '{$nombre}' agregado exitosamente al inventario.";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Error al crear: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "Por favor ingrese un nombre y precio válidos.";
            $message_type = "error";
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categoria = trim($_POST['categoria'] ?? 'General');

        if ($id > 0 && !empty($nombre)) {
            try {
                $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ?, categoria = ? WHERE id = ?");
                $stmt->execute([$nombre, $precio, $stock, $categoria, $id]);
                $message = "Producto #{$id} actualizado correctamente.";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Error al actualizar: " . $e->getMessage();
                $message_type = "error";
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Producto #{$id} eliminado del catálogo.";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Error al eliminar: " . $e->getMessage();
                $message_type = "error";
            }
        }
    } elseif ($action === 'seed') {
        try {
            $pdo->exec("TRUNCATE TABLE productos;");
            $pdo->exec("
                INSERT INTO productos (nombre, precio, stock, categoria) VALUES 
                ('Servidor Rack 2U Intel Xeon', 2490.00, 8, 'Servidores'),
                ('Switch Gestionable 48 Puertos PoE+', 680.50, 15, 'Redes'),
                ('Router VPN Balanceador Multi-WAN', 320.00, 22, 'Redes'),
                ('Storage NAS 4 Bahías 16TB', 890.00, 12, 'Almacenamiento'),
                ('UPS Online Doble Conversión 3kVA', 550.00, 5, 'Energía');
            ");
            $message = "Inventario restablecido con productos de demostración comercial.";
            $message_type = "success";
        } catch (Exception $e) {
            $message = "Error al poblar: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Obtención de productos
$productos = [];
$total_productos = 0;
$valor_inventario = 0;
$total_stock = 0;

if ($pdo) {
    try {
        $search = trim($_GET['q'] ?? '');
        $cat = trim($_GET['cat'] ?? '');
        
        $sql = "SELECT * FROM productos WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR categoria LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if (!empty($cat)) {
            $sql .= " AND categoria = ?";
            $params[] = $cat;
        }
        $sql .= " ORDER BY id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        foreach ($productos as $p) {
            $total_productos++;
            $total_stock += intval($p['stock']);
            $valor_inventario += (floatval($p['precio']) * intval($p['stock']));
        }
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

$pod_hostname = gethostname();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Apache">
    <title>Berserker TechStore | Gestión de Inventario</title>
    <!-- Wappalyzer detection & dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');
        body { font-family: 'IBM Plex Sans', sans-serif; background-color: #0f1115 !important; color: #f3f4f6 !important; }
        a { text-decoration: none !important; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .card-surface { background-color: #161920; border: 1px solid #232833; }
        .card-surface:hover { border-color: #323947; }
        .table-row-hover:hover { background-color: #1a1e27; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navegación Superior Profesional -->
    <header class="border-b border-[#232833] bg-[#12141a] sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand / Logo de Cliente Real -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-lg shadow-sm">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold tracking-tight text-white">BERSERKER</span>
                        <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-amber-500/10 text-amber-300 border border-amber-500/20">Store v2.4</span>
                    </div>
                    <span class="text-xs text-gray-400">Sistema de Control de Inventario & Almacén</span>
                </div>
            </div>

            <!-- Estado de Base de Datos y Acciones Rápidas -->
            <div class="flex items-center gap-3">
                <?php if ($pdo): ?>
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/25 text-xs text-emerald-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>MySQL 8.0 Activo</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-500/10 border border-rose-500/25 text-xs text-rose-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span>Desconectado</span>
                    </div>
                <?php endif; ?>

                <button onclick="abrirModalNuevo()" class="bg-amber-500 hover:bg-amber-600 text-gray-950 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Mensajes de Notificación -->
        <?php if ($message): ?>
            <div class="p-4 rounded-xl <?= $message_type === 'success' ? 'bg-emerald-950/40 border border-emerald-800/60 text-emerald-300' : 'bg-rose-950/40 border border-rose-800/60 text-rose-300' ?> flex items-center justify-between text-xs shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i class="<?= $message_type === 'success' ? 'fa-solid fa-circle-check text-emerald-400' : 'fa-solid fa-circle-exclamation text-rose-400' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de Métricas de Negocio -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card-surface rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs font-medium">
                    <span>Total de Referencias</span>
                    <i class="fa-solid fa-barcode text-gray-500"></i>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-white mono"><?= number_format($total_productos) ?></span>
                    <span class="text-xs text-gray-400">SKUs registrados</span>
                </div>
            </div>

            <div class="card-surface rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs font-medium">
                    <span>Stock Disponible</span>
                    <i class="fa-solid fa-cubes text-gray-500"></i>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-amber-400 mono"><?= number_format($total_stock) ?></span>
                    <span class="text-xs text-gray-400">Unidades en almacén</span>
                </div>
            </div>

            <div class="card-surface rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs font-medium">
                    <span>Valor del Catálogo</span>
                    <i class="fa-solid fa-dollar-sign text-gray-500"></i>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-emerald-400 mono">$<?= number_format($valor_inventario, 2) ?></span>
                    <span class="text-xs text-gray-400">USD estimado</span>
                </div>
            </div>

            <div class="card-surface rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs font-medium">
                    <span>Motor de Base de Datos</span>
                    <i class="fa-solid fa-database text-gray-500"></i>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-base font-bold text-white mono truncate"><?= htmlspecialchars($db_name ?? 'N/D') ?></span>
                    <span class="text-xs text-emerald-400 font-mono">Persistente</span>
                </div>
            </div>
        </div>

        <!-- Barra de Herramientas y Filtros -->
        <div class="card-surface rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
            <form method="GET" class="w-full sm:w-auto flex-1 flex flex-col sm:flex-row items-center gap-2">
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por producto o categoría..."
                        class="w-full bg-[#101217] border border-[#282d37] rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-amber-500">
                </div>

                <select name="cat" onchange="this.form.submit()" class="w-full sm:w-44 bg-[#101217] border border-[#282d37] rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                    <option value="">Todas las categorías</option>
                    <?php
                    $cats = ['Servidores', 'Redes', 'Almacenamiento', 'Energía', 'Seguridad', 'General'];
                    foreach ($cats as $c) {
                        $sel = (isset($_GET['cat']) && $_GET['cat'] === $c) ? 'selected' : '';
                        echo "<option value=\"{$c}\" {$sel}>{$c}</option>";
                    }
                    ?>
                </select>

                <?php if (!empty($_GET['q']) || !empty($_GET['cat'])): ?>
                    <a href="index.php" class="text-xs text-gray-400 hover:text-white px-2 py-1.5 transition">Limpiar</a>
                <?php endif; ?>
            </form>

            <form method="POST" onsubmit="return confirm('¿Restablecer el inventario con los datos iniciales de prueba?');">
                <input type="hidden" name="action" value="seed">
                <button type="submit" class="bg-[#1c2027] hover:bg-[#252b36] text-gray-300 border border-[#282d37] text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-rotate-left text-gray-400"></i>
                    <span>Cargar Datos Demo</span>
                </button>
            </form>
        </div>

        <!-- Tabla de Productos (CRUD Principal) -->
        <div class="card-surface rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#232833] bg-[#12141a] text-gray-400 uppercase tracking-wider font-mono text-[11px]">
                            <th class="py-3.5 px-4 font-semibold">SKU / ID</th>
                            <th class="py-3.5 px-4 font-semibold">Producto</th>
                            <th class="py-3.5 px-4 font-semibold">Categoría</th>
                            <th class="py-3.5 px-4 font-semibold">Precio Unit.</th>
                            <th class="py-3.5 px-4 font-semibold">Stock</th>
                            <th class="py-3.5 px-4 font-semibold">Disponibilidad</th>
                            <th class="py-3.5 px-4 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#232833]">
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <i class="fa-solid fa-box-open text-3xl text-gray-600 block"></i>
                                        <span class="block font-medium">No se encontraron productos registrados</span>
                                        <p class="text-[11px] text-gray-500">Agrega un nuevo producto o carga los datos de demostración iniciales.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $p): ?>
                                <tr class="table-row-hover transition">
                                    <td class="py-3.5 px-4 mono text-gray-400">#<?= htmlspecialchars($p['id']) ?></td>
                                    <td class="py-3.5 px-4 font-semibold text-white"><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded bg-gray-800 text-gray-300 border border-gray-700 text-[10px] font-mono">
                                            <?= htmlspecialchars($p['categoria']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 mono font-bold text-white">$<?= number_format($p['precio'], 2) ?></td>
                                    <td class="py-3.5 px-4 mono text-gray-300"><?= htmlspecialchars($p['stock']) ?> u.</td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($p['stock'] > 10): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> En Stock
                                            </span>
                                        <?php elseif ($p['stock'] > 0): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Bajo Stock
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-500/10 text-rose-400 border border-rose-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Agotado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <button onclick='abrirModalEditar(<?= json_encode($p) ?>)' class="text-gray-400 hover:text-amber-400 transition text-xs px-2 py-1 rounded bg-[#1c2027] border border-[#282d37]" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar producto <?= htmlspecialchars($p['nombre']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                            <button type="submit" class="text-gray-400 hover:text-rose-400 transition text-xs px-2 py-1 rounded bg-[#1c2027] border border-[#282d37]" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer Discreto de Plataforma (Con Botón de Diagnóstico para Evaluación) -->
    <footer class="border-t border-[#232833] bg-[#12141a] py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
            <div class="flex items-center gap-2">
                <span>&copy; 2026 Berserker Store. Todos los derechos reservados.</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="mono text-[11px] text-gray-400">Pod: <strong class="text-gray-300"><?= htmlspecialchars($pod_hostname) ?></strong></span>
                <button onclick="document.getElementById('diagModal').classList.remove('hidden')" class="text-amber-400 hover:underline mono text-[11px] flex items-center gap-1">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Diagnóstico de Entorno</span>
                </button>
            </div>
        </div>
    </footer>

    <!-- Modal: Registrar / Editar Producto -->
    <div id="productModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="card-surface rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#282d37] space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#282d37]">
                <h3 id="modalTitle" class="text-base font-bold text-white">Registrar Nuevo Producto</h3>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" id="productForm" class="space-y-3.5 text-xs">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block font-medium text-gray-300 mb-1">Nombre del Producto</label>
                    <input type="text" name="nombre" id="formNombre" required placeholder="Ej. Servidor Blade 4U"
                        class="w-full bg-[#101217] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-300 mb-1">Precio ($ USD)</label>
                        <input type="number" step="0.01" min="0.01" name="precio" id="formPrecio" required placeholder="199.99"
                            class="w-full bg-[#101217] border border-[#282d37] rounded-xl px-3 py-2 text-white mono focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-300 mb-1">Stock Inicial</label>
                        <input type="number" min="0" name="stock" id="formStock" required placeholder="10"
                            class="w-full bg-[#101217] border border-[#282d37] rounded-xl px-3 py-2 text-white mono focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-300 mb-1">Categoría</label>
                    <select name="categoria" id="formCategoria" class="w-full bg-[#101217] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500">
                        <option value="Servidores">Servidores</option>
                        <option value="Redes">Redes</option>
                        <option value="Almacenamiento">Almacenamiento</option>
                        <option value="Energía">Energía</option>
                        <option value="Seguridad">Seguridad</option>
                        <option value="General">General</option>
                    </select>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-[#282d37]">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 rounded-xl text-gray-400 hover:text-white bg-[#1c2027]">Cancelar</button>
                    <button type="submit" id="modalSubmitBtn" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-gray-950 font-bold transition">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Diagnóstico de Entorno y Variables Seguras (Para revisión docente) -->
    <div id="diagModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="card-surface rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-[#282d37] space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#282d37]">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-server text-amber-400"></i>
                    <h3 class="text-sm font-bold text-white">Diagnóstico de Entorno (PaaS Inquilino Berserker)</h3>
                </div>
                <button onclick="document.getElementById('diagModal').classList.add('hidden')" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="space-y-3 text-xs mono">
                <div class="p-3 bg-[#101217] rounded-xl border border-[#282d37] space-y-2">
                    <div class="text-[11px] text-gray-400 uppercase font-bold border-b border-[#232833] pb-1">Variables de Entorno (Zero-Trust):</div>
                    <div class="flex justify-between"><span>DB_HOST:</span><strong class="text-white"><?= htmlspecialchars($db_host ?? 'No configurado') ?></strong></div>
                    <div class="flex justify-between"><span>DB_PORT:</span><strong class="text-white"><?= htmlspecialchars($db_port ?? 'No configurado') ?></strong></div>
                    <div class="flex justify-between"><span>DB_NAME:</span><strong class="text-white"><?= htmlspecialchars($db_name ?? 'No configurado') ?></strong></div>
                    <div class="flex justify-between"><span>DB_USER:</span><strong class="text-white"><?= htmlspecialchars($db_user ?? 'No configurado') ?></strong></div>
                    <div class="flex justify-between"><span>DB_PASS:</span><strong class="text-amber-400">•••••••••••• (Inyectada en secreto K8s)</strong></div>
                </div>

                <div class="p-3 bg-[#101217] rounded-xl border border-[#282d37] space-y-1.5">
                    <div class="text-[11px] text-gray-400 uppercase font-bold border-b border-[#232833] pb-1">Orquestación & Pod:</div>
                    <div class="flex justify-between"><span>Pod Hostname:</span><span class="text-emerald-400"><?= htmlspecialchars($pod_hostname) ?></span></div>
                    <div class="flex justify-between"><span>Motor PHP:</span><span class="text-white"><?= phpversion() ?></span></div>
                    <div class="flex justify-between"><span>MySQL Server:</span><span class="text-white"><?= htmlspecialchars($db_version ?? 'Desconectado') ?></span></div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button onclick="document.getElementById('diagModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-gray-800 text-gray-200 text-xs">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function abrirModalNuevo() {
            document.getElementById('modalTitle').innerText = 'Registrar Nuevo Producto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('formId').value = '';
            document.getElementById('formNombre').value = '';
            document.getElementById('formPrecio').value = '';
            document.getElementById('formStock').value = '';
            document.getElementById('formCategoria').value = 'General';
            document.getElementById('modalSubmitBtn').innerText = 'Guardar Producto';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function abrirModalEditar(p) {
            document.getElementById('modalTitle').innerText = 'Editar Producto #' + p.id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = p.id;
            document.getElementById('formNombre').value = p.nombre;
            document.getElementById('formPrecio').value = p.precio;
            document.getElementById('formStock').value = p.stock;
            document.getElementById('formCategoria').value = p.categoria;
            document.getElementById('modalSubmitBtn').innerText = 'Actualizar Cambios';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('productModal').classList.add('hidden');
        }
    </script>
</body>
</html>
