<?php
// admin/eventos/test-api-simple.php
// Arquivo de teste simples para verificar a API

header('Content-Type: text/html; charset=utf-8');

// Mostrar todos os erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste da API de Eventos</h1>";

// Teste 1: Verificar se podemos incluir o config
echo "<h2>1. Testando inclusão do config.php:</h2>";
$config_path = '../../config.php';
if (file_exists($config_path)) {
    echo "✅ Arquivo config.php encontrado<br>";
    try {
        require_once $config_path;
        echo "✅ Config.php incluído com sucesso<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao incluir config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Arquivo config.php NÃO encontrado<br>";
    echo "Caminho esperado: " . realpath('../../') . "/config.php<br>";
}

// Teste 2: Verificar sessão
echo "<h2>2. Testando sessão:</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "✅ Sessão iniciada<br>";
} else {
    echo "✅ Sessão já estava ativa<br>";
}

// Forçar login para teste
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Teste';
echo "✅ Usuário de teste logado<br>";

// Teste 3: Testar conexão com banco
echo "<h2>3. Testando banco de dados:</h2>";
if (function_exists('conectar_db')) {
    try {
        $pdo = conectar_db();
        echo "✅ Conexão com banco estabelecida<br>";
        
        // Testar query simples
        $result = $pdo->query("SELECT 1");
        if ($result) {
            echo "✅ Query de teste executada com sucesso<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro na conexão com banco: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Função conectar_db não existe<br>";
}

// Teste 4: Verificar funções necessárias
echo "<h2>4. Verificando funções necessárias:</h2>";
$functions = ['verificar_login', 'sanitizar', 'fazer_upload'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✅ Função $func existe<br>";
    } else {
        echo "⚠️ Função $func NÃO existe<br>";
    }
}

// Teste 5: Verificar diretórios
echo "<h2>5. Verificando diretórios de upload:</h2>";
$upload_base = '../../uploads/';
$upload_eventos = '../../uploads/eventos/';

if (file_exists($upload_base)) {
    echo "✅ Diretório uploads/ existe<br>";
    echo "Permissões: " . substr(sprintf('%o', fileperms($upload_base)), -4) . "<br>";
    if (is_writable($upload_base)) {
        echo "✅ Diretório uploads/ tem permissão de escrita<br>";
    } else {
        echo "❌ Diretório uploads/ SEM permissão de escrita<br>";
    }
} else {
    echo "❌ Diretório uploads/ NÃO existe<br>";
}

if (file_exists($upload_eventos)) {
    echo "✅ Diretório uploads/eventos/ existe<br>";
    echo "Permissões: " . substr(sprintf('%o', fileperms($upload_eventos)), -4) . "<br>";
    if (is_writable($upload_eventos)) {
        echo "✅ Diretório uploads/eventos/ tem permissão de escrita<br>";
    } else {
        echo "❌ Diretório uploads/eventos/ SEM permissão de escrita<br>";
    }
} else {
    echo "❌ Diretório uploads/eventos/ NÃO existe<br>";
    echo "Tentando criar...<br>";
    if (!file_exists($upload_base)) {
        mkdir($upload_base, 0777, true);
    }
    if (mkdir($upload_eventos, 0777, true)) {
        echo "✅ Diretório criado com sucesso<br>";
    } else {
        echo "❌ Erro ao criar diretório<br>";
    }
}

// Teste 6: Informações do PHP
echo "<h2>6. Informações do PHP:</h2>";
echo "Versão do PHP: " . phpversion() . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// Teste 7: Testar chamada direta à API
echo "<h2>7. Teste de chamada à API:</h2>";
?>

<button onclick="testarAPI()" class="btn btn-primary">Testar API via AJAX</button>
<div id="resultado" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; display: none;">
    <h4>Resultado:</h4>
    <pre id="resultado-content"></pre>
</div>

<hr>

<h2>8. Teste de envio de formulário:</h2>
<form id="testForm">
    <input type="hidden" name="action" value="salvar">
    <input type="text" name="titulo" value="Evento de Teste" class="form-control mb-2" placeholder="Título">
    <input type="text" name="slug" value="evento-teste-<?php echo time(); ?>" class="form-control mb-2" placeholder="Slug">
    <button type="submit" class="btn btn-success">Testar Envio de Formulário</button>
</form>
<div id="resultado-form" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; display: none;">
    <h4>Resultado do Formulário:</h4>
    <pre id="resultado-form-content"></pre>
</div>

<script>
async function testarAPI() {
    const resultDiv = document.getElementById('resultado');
    const resultContent = document.getElementById('resultado-content');
    
    resultDiv.style.display = 'block';
    resultContent.textContent = 'Testando...';
    
    try {
        console.log('Iniciando teste de API...');
        
        const formData = new FormData();
        formData.append('action', 'teste');
        
        console.log('Enviando requisição para api.php...');
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Status da resposta:', response.status);
        console.log('Headers:', response.headers);
        
        const text = await response.text();
        console.log('Resposta completa:', text);
        
        resultContent.textContent = 'Status: ' + response.status + '\n\n' + text;
        
        // Tentar parsear como JSON
        try {
            const json = JSON.parse(text);
            resultContent.textContent += '\n\nJSON parseado:\n' + JSON.stringify(json, null, 2);
        } catch (e) {
            resultContent.textContent += '\n\nNão é um JSON válido';
        }
        
    } catch (error) {
        console.error('Erro:', error);
        resultContent.textContent = 'Erro na requisição:\n' + error.message + '\n\nVerifique o console para mais detalhes.';
    }
}

// Teste de formulário
document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const resultDiv = document.getElementById('resultado-form');
    const resultContent = document.getElementById('resultado-form-content');
    
    resultDiv.style.display = 'block';
    resultContent.textContent = 'Enviando...';
    
    try {
        const formData = new FormData(this);
        
        console.log('Dados do formulário:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log('Resposta:', text);
        
        resultContent.textContent = 'Status: ' + response.status + '\n\n' + text;
        
        try {
            const json = JSON.parse(text);
            resultContent.textContent += '\n\nJSON parseado:\n' + JSON.stringify(json, null, 2);
        } catch (e) {
            resultContent.textContent += '\n\nNão é um JSON válido';
        }
        
    } catch (error) {
        console.error('Erro:', error);
        resultContent.textContent = 'Erro:\n' + error.message;
    }
});
</script>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f5f5f5;
}
h1, h2 { 
    color: #333; 
}
h2 { 
    margin-top: 30px; 
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}
pre { 
    background: #fff; 
    padding: 10px; 
    border-radius: 5px; 
    overflow-x: auto; 
    border: 1px solid #ddd;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin: 5px;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-success {
    background: #28a745;
    color: white;
}
.btn:hover {
    opacity: 0.9;
}
.form-control {
    width: 100%;
    max-width: 400px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
</style>