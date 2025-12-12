<!-- Contenido principal termina aquí (se cierra la etiqueta main) -->
</main>

<!-- Footer -->
<footer class="bg-pink-100 mt-12 py-10 border-t border-pink-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-pink-800">
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            
            <!-- Columna 1: Información del Colectivo -->
            <div>
                <h5 class="text-lg font-bold mb-3 text-pink-600">Colectivo C2C</h5>
                <p class="text-pink-700 text-sm">
                    Plataforma de E-commerce C2C que conecta a compradores con marcas independientes de Sinaloa.
                </p>
            </div>
            
            <!-- Columna 2: Legal -->
            <div>
                <h5 class="text-lg font-bold mb-3 text-pink-600">Legal</h5>
                <ul class="space-y-2 text-sm">

                    <!-- Enlace al Aviso de Seguridad y Privacidad -->
                    <li>
                        <a href="/colectivo_c2c/pages/aviso_seguridad.php" 
                           class="text-pink-700 hover:text-pink-900 transition">
                           Aviso de Seguridad y Privacidad
                        </a>
                    </li>

                    <!-- Enlace a Términos y Condiciones -->
                    <li>
                        <a href="/colectivo_c2c/pages/terminos_condiciones.php" 
                           class="text-pink-700 hover:text-pink-900 transition">
                           Términos y Condiciones
                        </a>
                    </li>

                    <li><a href="/colectivo_c2c/pages/politicas_devoluciones.php" class="text-pink-700 hover:text-pink-900 transition">Política de Devoluciones</a></li>
                </ul>
            </div>
            
            <!-- Columna 3: Ayuda y Contacto -->
            <div>
                <h5 class="text-lg font-bold mb-3 text-pink-600">Ayuda</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="text-pink-700 hover:text-pink-900 transition">Preguntas Frecuentes</a></li>
                    <li><a href="#" class="text-pink-700 hover:text-pink-900 transition">Contacto</a></li>
                    <li><a href="#" class="text-pink-700 hover:text-pink-900 transition">Seguimiento de Pedido</a></li>
                </ul>
            </div>

            <!-- Columna 4: Redes Sociales -->
            <div class="md:col-span-1">
                <h5 class="text-lg font-bold mb-3 text-pink-600">Síguenos</h5>
                <div class="flex space-x-4 text-2xl">
                    <a href="#" class="text-pink-700 hover:text-pink-900 transition"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-pink-700 hover:text-pink-900 transition"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-pink-300 text-center text-pink-600 text-xs">
            &copy; <?php echo date("Y"); ?> Colectivo CDI. Proyecto de E-commerce.
        </div>
    </div>
</footer>

<!-- Script FontAwesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
