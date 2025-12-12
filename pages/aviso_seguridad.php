<?php
$page_title = "Aviso de Seguridad y Privacidad | Colectivo CDI";
include '../includes/header.php'; 
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-pink-200 max-w-4xl mx-auto">

        <!-- TÍTULO -->
        <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b-4 border-pink-300 pb-3">
            Aviso de Seguridad y Privacidad
        </h1>

        <!-- INTRO -->
        <p class="mb-8 text-gray-700">
            Este documento informa sobre el tratamiento de sus datos personales y las medidas de seguridad implementadas para proteger su información dentro de nuestra plataforma.
        </p>

        <!-- SECCIÓN 1 -->
        <h2 class="text-2xl font-bold text-pink-600 mb-4 mt-6">1. Seguridad de la Información</h2>
        <div class="space-y-4 text-gray-700 leading-relaxed">
            <p>
                Utilizamos tecnologías de cifrado <strong class="text-pink-500">SSL/TLS</strong> para proteger la transmisión de datos sensibles, como la información de su cuenta y los detalles de pago.
            </p>
            <p>
                Sus contraseñas se almacenan mediante algoritmos de <strong class="text-pink-500">hashing seguro</strong> (password_hash), lo que hace imposible recuperar la contraseña original incluso para el personal administrativo.
            </p>
            <p>
                No almacenamos datos directos de tarjetas de crédito o débito. Todas las transacciones se procesan mediante <strong class="text-pink-500">pasarelas de pago certificadas</strong>.
            </p>
        </div>

        <!-- SECCIÓN 2 -->
        <h2 class="text-2xl font-bold text-pink-600 mb-4 mt-8">2. Recolección y Uso de Datos Personales</h2>
        <div class="space-y-4 text-gray-700 leading-relaxed">
            <p>
                <strong class="text-pink-500">Datos Recolectados:</strong> Nombre, apellido, correo electrónico, dirección de envío y número de teléfono.
            </p>
            <p>
                <strong class="text-pink-500">Finalidad:</strong> Gestionar pedidos, procesar pagos, realizar envíos y enviar información promocional cuando sea autorizado por el usuario.
            </p>
            <p>
                <strong class="text-pink-500">Derechos ARCO:</strong> Usted puede Acceder, Rectificar, Cancelar u Oponerse al tratamiento de sus datos. Para ejercer estos derechos, por favor contacte a DerechosColectivo@cdi.com.
            </p>
        </div>

        <!-- FECHA -->
        <p class="mt-8 text-sm text-gray-500">
            Última actualización: Noviembre 2025.
        </p>

    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>