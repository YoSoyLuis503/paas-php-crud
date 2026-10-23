# PaaS App 1: Catálogo de Productos Cloud (Stack Clásico)

![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![Kubernetes](https://img.shields.io/badge/Kubernetes-Minikube-326CE5?style=flat&logo=kubernetes&logoColor=white)
![License](https://img.shields.io/badge/Evaluación-Parcial%202-success)

Aplicación web desarrollada en **PHP 8.2** con módulo CRUD completo y conexión segura a **MySQL 8.0** administrada en plataforma PaaS Multi-Tenant sobre Kubernetes.

---

## 📋 Características Principales
- **Módulo CRUD Completo:** Creación, lectura, actualización y eliminación de productos de infraestructura cloud con validaciones.
- **Arquitectura 12-Factor App:** Cero credenciales quemadas en el código fuente. Toda la configuración se inyecta dinámicamente mediante variables de entorno desde el panel de control del PaaS.
- **Diagnóstico en Vivo:** Detección automática del pod réplica asignado (`gethostname()`), estado de conexión PDO y versión de la base de datos MySQL.
- **Seguridad en Contenedores:** Imagen optimizada basada en Alpine Linux ejecutada como usuario no root (`UID 101`) para cumplir con las políticas de seguridad de Kubernetes (`runAsNonRoot: true`).

---

## 🔐 Variables de Entorno Requeridas

| Variable | Descripción | Valor Típico en el Clúster |
| :--- | :--- | :--- |
| `DB_HOST` | Hostname del servicio MySQL interno | `mysql-service.berserker.svc.cluster.local` |
| `DB_PORT` | Puerto de escucha de MySQL | `3306` |
| `DB_NAME` | Nombre de la base de datos relacional | `paas_berserker_db` |
| `DB_USER` | Usuario asignado al inquilino | `berserker_user` |
| `DB_PASS` | Contraseña segura del inquilino | Inyectada por la Bóveda del PaaS |
| `PORT` | Puerto HTTP del contenedor | `8080` (o asignado dinámicamente) |

---

## 🚀 Despliegue en el PaaS
1. Ingrese al panel del PaaS (`http://192.168.88.128:5000`).
2. Seleccione el inquilino **Berserker**.
3. En la pestaña **Despliegue GitOps**, pegue la URL de este repositorio:
   ```text
   https://github.com/<tu-usuario>/paas-php-crud.git
   ```
4. Verifique que las credenciales en la **Bóveda de Variables** coincidan con su base de datos MySQL.
5. Haga clic en **🚀 Desplegar a Producción**. El pipeline automatizado clonará el código, compilará la imagen en Minikube y ejecutará el rollout con Helm.
