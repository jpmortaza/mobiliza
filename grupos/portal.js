/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/grupos-portal.js
 * Descrição: Lógica JavaScript para o painel do mobilizador.
 */

document.addEventListener('DOMContentLoaded', function() {
    const menuIcon = document.getElementById('menu-icon');
    const closeMenuIcon = document.getElementById('close-menu-icon');
    const sideMenu = document.getElementById('side-menu');
    const searchInput = document.getElementById('search-input');

    // Abre e fecha o menu lateral
    if (menuIcon) {
        menuIcon.addEventListener('click', function() {
            sideMenu.classList.add('open');
        });
    }

    if (closeMenuIcon) {
        closeMenuIcon.addEventListener('click', function() {
            sideMenu.classList.remove('open');
        });
    }

    // Fecha o menu ao clicar fora dele
    window.addEventListener('click', function(e) {
        if (!sideMenu.contains(e.target) && !menuIcon.contains(e.target) && sideMenu.classList.contains('open')) {
            sideMenu.classList.remove('open');
        }
    });

    // Lógica de busca dinâmica para a tabela
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            let filter = searchInput.value.toLowerCase();
            let table = document.getElementById('indicados-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 0; i < tr.length; i++) {
                // Pula a linha do cabeçalho
                if (tr[i].getElementsByTagName('th').length > 0) {
                    continue;
                }
                
                let tdName = tr[i].getElementsByTagName('td')[0];
                let tdWhatsapp = tr[i].getElementsByTagName('td')[1];
                let tdCity = tr[i].getElementsByTagName('td')[2];
                
                if (tdName || tdWhatsapp || tdCity) {
                    let textValue = (tdName.textContent || tdName.innerText) + ' ' + 
                                    (tdWhatsapp.textContent || tdWhatsapp.innerText) + ' ' +
                                    (tdCity.textContent || tdCity.innerText);
                    
                    if (filter === 'todos' || textValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        });
    }
});
