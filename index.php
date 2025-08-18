<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags - Foco em Otimização de Busca -->
    <title>Mobiliza+ | A Plataforma para sua Mobilização</title>
    <meta name="description" content="Mobiliza+ é a plataforma completa para centralizar e potencializar a mobilização de sua causa. Crie eventos, colete assinaturas para petições, e gerencie seu público em um só lugar.">
    <meta name="keywords" content="plataforma de mobilização, eventos políticos, abaixo-assinado online, petição, CRM, ativismo social, organização política, engajamento">
    <meta name="author" content="Mobiliza+">
    
    <!-- Open Graph Meta Tags para Redes Sociais -->
    <meta property="og:title" content="Mobiliza+: A plataforma que centraliza sua mobilização">
    <meta property="og:description" content="Chega de ferramentas dispersas. Com Mobiliza+, você tem tudo que precisa para sua causa em um único lugar.">
    <meta property="og:image" content="https://placehold.co/1200x630/0d9488/ffffff?text=Mobiliza+%2B">
    <meta property="og:url" content="https://seusite.com.br">
    <meta property="og:type" content="website">

    <!-- Tailwind CSS via CDN para estilização rápida e responsiva -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonte personalizada -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Estilos globais personalizados -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1a202c; /* Cor de texto padrão */
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">

    <!-- Cabeçalho (Header) da página com navegação -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="#" class="text-2xl font-bold text-teal-600 hover:text-teal-800 transition duration-300">
                Mobiliza+
            </a>
            <div class="hidden md:flex space-x-6 items-center">
                <a href="#sobre" class="text-gray-600 hover:text-teal-600 transition duration-300">Sobre</a>
                <a href="#ferramentas" class="text-gray-600 hover:text-teal-600 transition duration-300">Ferramentas</a>
                <a href="#comece" class="text-gray-600 hover:text-teal-600 transition duration-300">Comece Agora</a>
                <a href="#contato" class="text-gray-600 hover:text-teal-600 transition duration-300">Contato</a>
                <a href="#" class="bg-teal-600 text-white font-semibold py-2 px-6 rounded-full shadow-md hover:bg-teal-700 transition duration-300 transform hover:scale-105">
                    Entrar
                </a>
            </div>
            <!-- Menu hamburguer para mobile -->
            <div class="md:hidden">
                <button id="menu-btn" class="text-gray-600 hover:text-teal-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </nav>
        <!-- Menu mobile expandido -->
        <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg">
            <div class="flex flex-col px-4 py-2 space-y-2">
                <a href="#sobre" class="block text-gray-600 hover:bg-gray-100 p-2 rounded transition duration-300">Sobre</a>
                <a href="#ferramentas" class="block text-gray-600 hover:bg-gray-100 p-2 rounded transition duration-300">Ferramentas</a>
                <a href="#comece" class="block text-gray-600 hover:bg-gray-100 p-2 rounded transition duration-300">Comece Agora</a>
                <a href="#contato" class="block text-gray-600 hover:bg-gray-100 p-2 rounded transition duration-300">Contato</a>
                <a href="#" class="block text-center bg-teal-600 text-white font-semibold p-2 rounded-full mt-2 hover:bg-teal-700 transition duration-300">Entrar</a>
            </div>
        </div>
    </header>

    <!-- Conteúdo principal da página -->
    <main>

        <!-- Seção de destaque (Hero Section) -->
        <section class="bg-gradient-to-r from-teal-500 to-green-600 text-white py-24 md:py-32">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-4">
                    Toda sua mobilização em um só lugar.
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                    Chega de gerenciar sua causa com ferramentas dispersas. O Mobiliza+ centraliza a gestão de eventos, abaixo-assinados e contatos.
                </p>
                <a href="#comece" class="bg-white text-teal-600 font-semibold text-lg py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition duration-300 transform hover:scale-105">
                    Comece a Mobilizar
                </a>
            </div>
        </section>

        <!-- Seção "Sobre Nós" -->
        <section id="sobre" class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/2">
                        <img src="https://placehold.co/800x600/b1c9c7/ffffff?text=Plataforma+Mobiliza%2B" alt="" class="rounded-3xl shadow-xl">
                    </div>
                    <div class="md:w-1/2">
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">A Plataforma que sua Causa Precisa</h2>
                        <p class="text-gray-600 text-lg mb-4">
                            O Mobiliza+ nasceu da necessidade de uma ferramenta completa para ativistas, organizadores e movimentos sociais. Nosso objetivo é simplificar a gestão e potencializar o impacto da sua mobilização.
                        </p>
                        <p class="text-gray-600 text-lg">
                            Com uma interface intuitiva e funcionalidades robustas, você pode focar no que realmente importa: conectar com o seu público e lutar pelas suas causas.
                        </p>
                        <a href="#ferramentas" class="mt-6 inline-block bg-teal-600 text-white font-semibold py-3 px-8 rounded-full shadow-md hover:bg-teal-700 transition duration-300">
                            Conheça as Ferramentas
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Seção de "Chamada para Ação" -->
        <section id="ferramentas" class="py-20 bg-gray-100">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-8">Nossas Principais Ferramentas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-3xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="text-5xl text-teal-600 mb-4">📅</div>
                        <h3 class="text-xl font-semibold mb-2">Eventos</h3>
                        <p class="text-gray-600">
                            Crie e gerencie eventos online e presenciais, desde a inscrição até o check-in dos participantes.
                        </p>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="text-5xl text-green-600 mb-4">✍️</div>
                        <h3 class="text-xl font-semibold mb-2">Abaixo-assinados</h3>
                        <p class="text-gray-600">
                            Lance petições online e colete assinaturas para dar mais força à sua causa.
                        </p>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="text-5xl text-blue-600 mb-4">📞</div>
                        <h3 class="text-xl font-semibold mb-2">CRM</h3>
                        <p class="text-gray-600">
                            Gerencie todos os seus contatos em um CRM simples e eficiente para segmentação e comunicação.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção de participação -->
        <section id="comece" class="py-20 bg-white text-center">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-gray-800 mb-8">Comece a Usar o Mobiliza+</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
                    Está pronto para levar sua mobilização para o próximo nível? Cadastre-se e explore todas as ferramentas que o Mobiliza+ oferece.
                </p>
                <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                    <a href="#" class="bg-blue-600 text-white font-semibold text-lg py-3 px-8 rounded-full shadow-md hover:bg-blue-700 transition duration-300 transform hover:scale-105">
                        Criar Conta
                    </a>
                    <a href="#" class="bg-gray-800 text-white font-semibold text-lg py-3 px-8 rounded-full shadow-md hover:bg-gray-900 transition duration-300 transform hover:scale-105">
                        Fazer Login
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Rodapé (Footer) -->
    <footer class="bg-gray-800 text-gray-300 py-12">
        <div class="container mx-auto px-4 text-center md:text-left">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4">Mobiliza+</h3>
                    <p class="text-sm">
                        Centralize, organize e potencialize sua causa. Juntos, fazemos a diferença.
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4">Links Rápidos</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#sobre" class="hover:text-white transition duration-300">Sobre</a></li>
                        <li><a href="#ferramentas" class="hover:text-white transition duration-300">Ferramentas</a></li>
                        <li><a href="#comece" class="hover:text-white transition duration-300">Comece Agora</a></li>
                        <li><a href="#contato" class="hover:text-white transition duration-300">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4">Conecte-se</h3>
                    <div class="flex justify-center md:justify-start space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-sm">
                &copy; <?php echo date("Y"); ?> Mobiliza+. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script>
        // Lógica para o menu mobile
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Fechar o menu mobile ao clicar em um link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
    </script>

</body>
</html>
