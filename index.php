<?php
require_once __DIR__ . '/db.php';

$message = null;
$message_type = 'info';

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
                $message = "Producto '{$nombre}' agregado exitosamente al catálogo.";
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
                $message = "Producto #{$id} eliminado de la base de datos.";
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
                ('Servidor Cloud VPS 4GB', 24.99, 15, 'Computo'),
                ('Almacenamiento NVMe 100GB', 9.99, 50, 'Storage'),
                ('Balanceador de Carga HA', 14.50, 8, 'Redes'),
                ('Base de Datos MySQL HA', 39.00, 5, 'Base de Datos'),
                ('Certificado SSL Wildcard', 12.00, 100, 'Seguridad');
            ");
            $message = "Catálogo restablecido con datos de demostración iniciales.";
            $message_type = "success";
        } catch (Exception $e) {
            $message = "Error al poblar: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Obtención de registros
$productos = [];
$total_productos = 0;
$valor_inventario = 0;
$total_stock = 0;

if ($pdo) {
    try {
        $search = trim($_GET['q'] ?? '');
        if (!empty($search)) {
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR categoria LIKE ? ORDER BY id DESC");
            $stmt->execute(["%{$search}%", "%{$search}%"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
        }
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
    <title>PaaS App 1 | CRUD PHP 8.2 + MySQL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans', sans-serif; background-color: #121417; color: #F5E9E7; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .card-glass { background-color: #181b20; border: 1px solid #282d37; }
        .badge-pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.15rem 0.6rem; border-radius: 9999px; font-size: 0.72rem; font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-screen p-4 sm:p-6 lg:p-8">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Top Header & Banner de Gobernanza PaaS -->
        <header class="card-glass rounded-2xl p-5 sm:p-6 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-[#837D68]/20 border border-[#837D68]/40 flex items-center justify-center text-[#837D68] text-2xl">
                    <i class="fa-brands fa-php"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Catálogo de Productos Cloud</h1>
                        <span class="badge-pill bg-[#837D68]/20 text-[#837D68] border border-[#837D68]/30">Stack Clásico #1</span>
                    </div>
                    <p class="text-xs text-[#C1C0C2] mt-0.5">Aplicación CRUD en PHP 8.2 conectada a MySQL 8.0 administrada en PaaS (Tenant: <span class="mono text-white font-semibold">berserker</span>)</p>
                </div>
            </div>

            <!-- Auditoría Pod & Réplicas en vivo -->
            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <div class="card-glass px-3 py-2 rounded-xl text-right">
                    <span class="block text-[10px] text-[#C1C0C2] uppercase mono">Pod Kubernetes</span>
                    <span class="mono text-xs font-semibold text-[#8A9DB1] truncate max-w-[200px] block" title="<?= htmlspecialchars($pod_hostname) ?>">
                        <i class="fa-solid fa-cube text-[10px] mr-1"></i><?= htmlspecialchars($pod_hostname) ?>
                    </span>
                </div>
                <div class="card-glass px-3 py-2 rounded-xl text-right">
                    <span class="block text-[10px] text-[#C1C0C2] uppercase mono">Conexión MySQL</span>
                    <?php if ($pdo): ?>
                        <span class="badge-pill bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ONLINE
                        </span>
                    <?php else: ?>
                        <span class="badge-pill bg-rose-500/20 text-rose-400 border border-rose-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> OFFLINE
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Banner de Variables de Entorno (Evidencia para Rúbrica) -->
        <section class="card-glass rounded-2xl p-4 sm:p-5 text-xs">
            <div class="flex items-center justify-between mb-3 border-b border-[#282d37] pb-2">
                <span class="font-bold text-[#C1C0C2] uppercase tracking-wider mono flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-[#ECC5C6]"></i> Bóveda de Variables de Entorno (12-Factor App)
                </span>
                <span class="text-[11px] text-[#837D68] mono">Sin credenciales expuestas en repositorio Git</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mono">
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">DB_HOST</span>
                    <span class="text-white font-semibold truncate block" title="<?= htmlspecialchars($db_host) ?>"><?= htmlspecialchars($db_host) ?></span>
                </div>
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">DB_PORT</span>
                    <span class="text-white font-semibold block"><?= htmlspecialchars($db_port) ?></span>
                </div>
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">DB_NAME</span>
                    <span class="text-white font-semibold block"><?= htmlspecialchars($db_name) ?></span>
                </div>
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">DB_USER</span>
                    <span class="text-white font-semibold block"><?= htmlspecialchars($db_user) ?></span>
                </div>
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">DB_PASS</span>
                    <span class="text-[#ECC5C6] font-semibold block">••••••••••••</span>
                </div>
                <div class="bg-[#121417] p-2.5 rounded-lg border border-[#282d37]">
                    <span class="text-[10px] text-[#C1C0C2] block">MOTOR / VER</span>
                    <span class="text-[#8A9DB1] font-semibold truncate block"><?= htmlspecialchars($db_version ?? 'Desconectado') ?></span>
                </div>
            </div>
            <?php if ($db_error): ?>
                <div class="mt-3 p-3 rounded-lg bg-rose-950/40 border border-rose-800/60 text-rose-300 mono text-xs flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><strong>Error de Conexión:</strong> <?= htmlspecialchars($db_error) ?></span>
                </div>
            <?php endif; ?>
        </section>

        <!-- Alertas dinámicas -->
        <?php if ($message): ?>
            <div class="p-4 rounded-xl <?= $message_type === 'success' ? 'bg-emerald-950/40 border border-emerald-800/60 text-emerald-200' : 'bg-rose-950/40 border border-rose-800/60 text-rose-200' ?> flex items-center justify-between text-sm shadow-md">
                <div class="flex items-center gap-2">
                    <i class="<?= $message_type === 'success' ? 'fa-solid fa-circle-check text-emerald-400' : 'fa-solid fa-circle-exclamation text-rose-400' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <a href="index.php" class="text-xs opacity-70 hover:opacity-100 mono underline">Cerrar</a>
            </div>
        <?php endif; ?>

        <!-- Métricas Rápidas del CRUD -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card-glass p-4 rounded-2xl">
                <span class="text-xs text-[#C1C0C2] uppercase mono block">Total de Productos</span>
                <span class="text-2xl font-bold text-white mono mt-1 block"><?= $total_productos ?></span>
            </div>
            <div class="card-glass p-4 rounded-2xl">
                <span class="text-xs text-[#C1C0C2] uppercase mono block">Unidades en Stock</span>
                <span class="text-2xl font-bold text-[#8A9DB1] mono mt-1 block"><?= $total_stock ?> uds.</span>
            </div>
            <div class="card-glass p-4 rounded-2xl">
                <span class="text-xs text-[#C1C0C2] uppercase mono block">Valor Total en Almacén</span>
                <span class="text-2xl font-bold text-[#ECC5C6] mono mt-1 block">$<?= number_format($valor_inventario, 2) ?></span>
            </div>
        </div>

        <!-- Barra de Controles y Búsqueda -->
        <div class="card-glass rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="GET" class="w-full sm:w-auto flex-1 flex gap-2">
                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por producto o categoría..." class="w-full sm:max-w-xs bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-sm text-white placeholder-[#C1C0C2]/50 focus:outline-none focus:border-[#837D68]">
                <button type="submit" class="bg-[#181b20] hover:bg-[#282d37] border border-[#282d37] px-3 py-2 rounded-xl text-xs mono font-semibold">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if (!empty($_GET['q'])): ?>
                    <a href="index.php" class="bg-[#181b20] hover:bg-[#282d37] border border-[#282d37] px-3 py-2 rounded-xl text-xs mono text-[#C1C0C2] flex items-center">Limpiar</a>
                <?php endif; ?>
            </form>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <form method="POST" onsubmit="return confirm('¿Restablecer el catálogo a los datos seed de prueba?');">
                    <input type="hidden" name="action" value="seed">
                    <button type="submit" class="bg-[#181b20] hover:bg-[#282d37] text-[#C1C0C2] border border-[#282d37] px-3.5 py-2 rounded-xl text-xs mono flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-rotate-left"></i> Restablecer Seed
                    </button>
                </form>
                <button onclick="document.getElementById('modalCrear').classList.remove('hidden')" class="bg-[#837D68] hover:bg-[#968f77] text-white px-4 py-2 rounded-xl text-xs mono font-semibold flex items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-plus"></i> Nuevo Producto
                </button>
            </div>
        </div>

        <!-- Tabla CRUD Principal -->
        <div class="card-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#121417] text-[#C1C0C2] uppercase text-[11px] mono border-b border-[#282d37]">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">ID</th>
                            <th class="py-3.5 px-4 font-semibold">Producto</th>
                            <th class="py-3.5 px-4 font-semibold">Categoría</th>
                            <th class="py-3.5 px-4 font-semibold">Precio</th>
                            <th class="py-3.5 px-4 font-semibold">Stock</th>
                            <th class="py-3.5 px-4 font-semibold">Registrado</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282d37]/60">
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="py-10 text-center text-[#C1C0C2]">
                                    <i class="fa-solid fa-box-open text-3xl mb-2 text-[#282d37] block"></i>
                                    No se encontraron productos en la base de datos MySQL.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $row): ?>
                                <tr class="hover:bg-[#1c2026] transition">
                                    <td class="py-3 px-4 mono text-xs text-[#C1C0C2]">#<?= htmlspecialchars($row['id']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-white"><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="badge-pill bg-[#282d37] text-[#C1C0C2] border border-[#282d37]/80">
                                            <?= htmlspecialchars($row['categoria']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 mono text-[#ECC5C6] font-semibold">$<?= number_format(floatval($row['precio']), 2) ?></td>
                                    <td class="py-3 px-4 mono">
                                        <?php if (intval($row['stock']) <= 5): ?>
                                            <span class="text-rose-400 font-bold"><?= htmlspecialchars($row['stock']) ?> (Bajo)</span>
                                        <?php else: ?>
                                            <span class="text-white"><?= htmlspecialchars($row['stock']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 mono text-xs text-[#C1C0C2]"><?= htmlspecialchars(substr($row['creado_en'] ?? '', 0, 16)) ?></td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button onclick="editarProducto(<?= htmlspecialchars(json_encode($row)) ?>)" class="bg-[#121417] hover:bg-[#282d37] text-[#8A9DB1] p-1.5 rounded-lg border border-[#282d37] transition" title="Editar">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('¿Confirma eliminar <?= htmlspecialchars(addslashes($row['nombre'])) ?>?');" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="bg-[#121417] hover:bg-rose-950/50 text-rose-400 p-1.5 rounded-lg border border-[#282d37] transition" title="Eliminar">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Informativo para el Parcial -->
        <footer class="text-center text-xs text-[#C1C0C2] pt-4 border-t border-[#282d37]/50 mono">
            Informática en la Nube • Parcial 2 (Cómputo 2) • Aplicación 1 (Stack Clásico PHP 8.2 + MySQL 8.0)
        </footer>

    </div>

    <!-- Modal Crear Producto -->
    <div id="modalCrear" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="card-glass rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#282d37] space-y-4">
            <div class="flex items-center justify-between border-b border-[#282d37] pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-[#837D68]"></i> Agregar Nuevo Producto
                </h3>
                <button onclick="document.getElementById('modalCrear').classList.add('hidden')" class="text-[#C1C0C2] hover:text-white">&times;</button>
            </div>
            <form method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-[#C1C0C2] mono uppercase mb-1">Nombre del Producto</label>
                    <input type="text" name="nombre" required placeholder="Ej: Balanceador NGINX Plus" class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#837D68]">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#C1C0C2] mono uppercase mb-1">Precio ($ USD)</label>
                        <input type="number" step="0.01" min="0.01" name="precio" required placeholder="19.99" class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#837D68]">
                    </div>
                    <div>
                        <label class="block text-[#C1C0C2] mono uppercase mb-1">Stock Inicial</label>
                        <input type="number" min="0" name="stock" required value="10" class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#837D68]">
                    </div>
                </div>
                <div>
                    <label class="block text-[#C1C0C2] mono uppercase mb-1">Categoría</label>
                    <select name="categoria" class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#837D68]">
                        <option value="Computo">Cómputo</option>
                        <option value="Storage">Almacenamiento</option>
                        <option value="Redes">Redes & CDN</option>
                        <option value="Base de Datos">Base de Datos</option>
                        <option value="Seguridad">Seguridad & SSL</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalCrear').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-[#282d37] text-[#C1C0C2] hover:bg-[#282d37]">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#837D68] hover:bg-[#968f77] text-white font-semibold">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div id="modalEditar" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="card-glass rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#282d37] space-y-4">
            <div class="flex items-center justify-between border-b border-[#282d37] pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-[#8A9DB1]"></i> Modificar Producto
                </h3>
                <button onclick="document.getElementById('modalEditar').classList.add('hidden')" class="text-[#C1C0C2] hover:text-white">&times;</button>
            </div>
            <form method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div>
                    <label class="block text-[#C1C0C2] mono uppercase mb-1">Nombre del Producto</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#8A9DB1]">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#C1C0C2] mono uppercase mb-1">Precio ($ USD)</label>
                        <input type="number" step="0.01" min="0.01" name="precio" id="edit_precio" required class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#8A9DB1]">
                    </div>
                    <div>
                        <label class="block text-[#C1C0C2] mono uppercase mb-1">Stock</label>
                        <input type="number" min="0" name="stock" id="edit_stock" required class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#8A9DB1]">
                    </div>
                </div>
                <div>
                    <label class="block text-[#C1C0C2] mono uppercase mb-1">Categoría</label>
                    <select name="categoria" id="edit_categoria" class="w-full bg-[#121417] border border-[#282d37] rounded-xl px-3 py-2 text-white focus:outline-none focus:border-[#8A9DB1]">
                        <option value="Computo">Cómputo</option>
                        <option value="Storage">Almacenamiento</option>
                        <option value="Redes">Redes & CDN</option>
                        <option value="Base de Datos">Base de Datos</option>
                        <option value="Seguridad">Seguridad & SSL</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalEditar').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-[#282d37] text-[#C1C0C2] hover:bg-[#282d37]">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#8A9DB1] hover:bg-[#9ab0c5] text-white font-semibold">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editarProducto(prod) {
            document.getElementById('edit_id').value = prod.id;
            document.getElementById('edit_nombre').value = prod.nombre;
            document.getElementById('edit_precio').value = prod.precio;
            document.getElementById('edit_stock').value = prod.stock;
            document.getElementById('edit_categoria').value = prod.categoria;
            document.getElementById('modalEditar').classList.remove('hidden');
        }
    </script>
</body>
</html>
