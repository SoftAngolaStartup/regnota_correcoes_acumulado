<?php
/**
 * public/index.php
 * Portal Público do Estudante
 *
 * Funcionalidades:
 *  - Submissão de ocorrências (tab "Registar")
 *  - Consulta por ticket ou nome (tab "Consultar")
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar};
use App\Models\{OcorrenciaModelo, CursoModelo, TurmaModelo,
               SemestreModelo, DisciplinaModelo, AnoLetivoModelo};

Sessao::iniciar();

$om  = new OcorrenciaModelo();
$cm  = new CursoModelo();
$tm  = new TurmaModelo();
$sm  = new SemestreModelo();
$dm  = new DisciplinaModelo();
$alm = new AnoLetivoModelo();

$abaActiva  = $_GET['aba'] ?? 'registar';
$flash      = null;
$formValues = [];
$ticketGerado = '';

// ── Processar submissão ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'submeter') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        $flash = ['tipo'=>'erro','msg'=>'Pedido inválido. Recarregue a página.'];
    } else {
        $dados = [
            'estudante_nome'     => Sanitizar::texto($_POST['estudante_nome']     ?? ''),
            'estudante_numero'   => Sanitizar::texto($_POST['estudante_numero']   ?? ''),
            'estudante_telefone' => Sanitizar::texto($_POST['estudante_telefone'] ?? ''),
            'estudante_email'    => Sanitizar::texto($_POST['estudante_email']    ?? ''),
            'curso_id'           => (int)($_POST['curso_id']     ?? 0),
            'turma_id'           => (int)($_POST['turma_id']     ?? 0),
            'semestre_id'        => (int)($_POST['semestre_id']  ?? 0),
            'disciplina_id'      => (int)($_POST['disciplina_id']?? 0),
            'tipo_avaliacao'     => $_POST['tipo_avaliacao'] ?? '',
            'descricao'          => Sanitizar::texto($_POST['descricao'] ?? ''),
            'ip_origem'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        $formValues = $dados;

        $erros = [];
        if (!$dados['estudante_nome'])                              $erros[] = 'Nome completo é obrigatório.';
        if (!$dados['estudante_numero'])                            $erros[] = 'Número de estudante é obrigatório.';
        if (!$dados['estudante_telefone'])                          $erros[] = 'Telefone é obrigatório.';
        if (!filter_var($dados['estudante_email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
        if (!$dados['curso_id'])                                    $erros[] = 'Seleccione o curso.';
        if (!$dados['turma_id'])                                    $erros[] = 'Seleccione a turma.';
        if (!$dados['semestre_id'])                                 $erros[] = 'Seleccione o semestre.';
        if (!$dados['disciplina_id'])                               $erros[] = 'Seleccione a disciplina.';
        if (!array_key_exists($dados['tipo_avaliacao'], Etiqueta::todosTipos())) $erros[] = 'Seleccione o tipo de avaliação.';
        if (strlen($dados['descricao']) < 15)                       $erros[] = 'A descrição deve ter pelo menos 15 caracteres.';

        if ($erros) {
            $flash = ['tipo'=>'erro','msg'=>implode(' ', $erros)];
        } else {
            $resultado    = $om->criar($dados);
            $ticketGerado = $resultado['ticket'];
            $flash = ['tipo'=>'sucesso','msg'=>"Ocorrência submetida com sucesso! O seu ticket é <strong>{$ticketGerado}</strong>. Guarde este código para consultar o estado."];
            $formValues = []; // Limpar formulário
            $abaActiva  = 'registar';
        }
    }
}

// ── Dados para selects do formulário ─────────────────────
$cursos       = $cm->listarTodos(true);
$anosLetivos  = $alm->listarTodos();
$anoAtivo     = $alm->obterAtivo();
$semestres    = $sm->listarAtivos();
$turmasAno    = $tm->listarDoAnoAtivo();

// Todas as disciplinas activas em JSON (carregamento dinâmico)
$todasDiscs = $dm->listarTodas(['ativo' => 1]);
$discsJson  = json_encode(array_map(fn($d) => [
    'id'         => (int)$d['id'],
    'nome'       => $d['nome'],
    'codigo'     => $d['codigo'] ?? '',
    'curso_id'   => (int)$d['curso_id'],
    'ano_nivel'  => (int)$d['ano_nivel'],
    'sem_num'    => (int)$d['semestre_num'],
    'prof'       => $d['professor_nome'],
], $todasDiscs));

// Turmas em JSON para filtro dinâmico
$turmasJson = json_encode(array_map(fn($t) => [
    'id'       => (int)$t['id'],
    'codigo'   => $t['codigo'],
    'curso_id' => (int)$t['curso_id'],
    'ano_nivel'=> (int)$t['ano_nivel'],
    'label'    => $t['codigo'] . ' – ' . Etiqueta::anoNivel((int)$t['ano_nivel']),
], $tm->listarTodas()));

// Semestres em JSON
$semsJson = json_encode(array_map(fn($s) => [
    'id'     => (int)$s['id'],
    'nome'   => $s['nome'],
    'num'    => (int)$s['numero'],
    'ano_id' => (int)$s['ano_letivo_id'],
], $sm->listarTodos()));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal do Estudante | <?= APP_NOME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require APP_DIR . '/views/shared/tema_head.php'; ?>
  <link rel="stylesheet" type="text/css" href="<?= \App\Helpers\Url::asset('css/regnotas.css') ?>">
  <style>
    body { background: var(--surface-body); font-family:var(--font-family); }
    /* Topbar */
    .portal-topbar { background:var(--surface-sidebar); border-bottom:3px solid var(--marcador); padding:0; position:sticky; top:0; z-index:100; box-shadow:0 2px 16px rgba(11,29,58,.3); }
    .portal-topbar-inner { display:flex; align-items:center; justify-content:space-between; padding:13px 24px; max-width:860px; margin:0 auto; }
    .portal-logo { display:flex; align-items:center; gap:11px; text-decoration:none; color:#fff; }
    .portal-logo-icon { width:38px; height:38px; background:var(--academic-gold); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:900; color:var(--academic-navy); }
    .portal-logo-nome  { font-size:.95rem; font-weight:700; }
    .portal-logo-sub   { font-size:.68rem; opacity:.65; }
    .portal-nav { display:flex; gap:3px; }
    .portal-nav a { color:rgba(255,255,255,.72); text-decoration:none; font-size:.82rem; padding:7px 14px; border-radius:var(--radius-sm); font-weight:600; transition:all .18s; display:flex; align-items:center; gap:6px; font-family:var(--font-family); }
    .portal-nav a:hover, .portal-nav a.activo { background:rgba(255,255,255,.14); color:#fff; }
    /* Hero */
    .portal-hero { background:var(--surface-sidebar); background-image:repeating-linear-gradient(0deg,transparent 0 31px,rgba(255,255,255,.045) 31px 32px); padding:44px 24px 32px; text-align:center; color:#fff; }
    .portal-hero h1 { font-size:var(--text-display); font-weight:800; margin-bottom:6px; }
    .portal-hero p  { opacity:.78; font-size:var(--text-md); max-width:480px; margin:0 auto; }
    /* Abas */
    .aba-switcher { display:flex; background:var(--surface-card); border-bottom:2px solid var(--border-default); position:sticky; top:62px; z-index:50; }
    .aba-btn { flex:1; padding:15px; font-family:var(--font-family); font-weight:700; font-size:var(--text-sm); border:none; background:transparent; color:var(--text-secondary); cursor:pointer; transition:all .18s; display:flex; align-items:center; justify-content:center; gap:8px; border-bottom:3px solid transparent; margin-bottom:-2px; }
    .aba-btn:hover  { color:var(--academic-blue); background:var(--surface-body); }
    .aba-btn.activa { color:var(--academic-blue); border-bottom-color:var(--academic-gold); }
    /* Form */
    .form-card { background:var(--surface-card); border-radius:var(--radius-lg); border:1px solid var(--border-default); box-shadow:var(--shadow-sm); overflow:hidden; }
    .form-card-topo { background:var(--surface-body); border-bottom:1px solid var(--border-default); padding:var(--space-4) var(--space-6); display:flex; align-items:center; gap:12px; }
    .form-card-ico  { width:38px; height:38px; background:var(--surface-sidebar); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--academic-gold); font-size:17px; flex-shrink:0; }
    .form-card-corpo { padding:var(--space-6); }
    /* Tipo avaliação */
    .tipo-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
    .tipo-etiqueta { cursor:pointer; text-align:center; border:1.5px solid var(--border-default); border-radius:var(--radius-md); padding:10px 8px; font-size:var(--text-sm); font-weight:700; color:var(--text-secondary); transition:all .18s; font-family:var(--font-family); }
    .tipo-etiqueta:hover { border-color:var(--academic-blue-light); color:var(--academic-blue); }
    input[type=radio]:checked + .tipo-etiqueta { border-color:var(--quadro); background:var(--quadro); color:var(--text-on-primary); }
    input[type=radio].tipo-radio { display:none; }
    /* Prof display */
    .prof-display { border:1.5px solid var(--border-default); border-radius:var(--radius-md); padding:10px var(--space-4); background:var(--surface-body); font-size:var(--text-sm); color:var(--text-secondary); min-height:42px; display:flex; align-items:center; gap:8px; font-family:var(--font-family); }
    .prof-display.preenchido { color:var(--text-primary); font-weight:600; background:var(--surface-raised); }
    /* Consulta */
    .ticket-resultado { background:var(--surface-card); border-radius:var(--radius-lg); border:2px solid var(--border-default); overflow:hidden; }
    .ticket-resultado-topo { background:var(--surface-sidebar); color:#fff; padding:14px var(--space-4); display:flex; align-items:center; justify-content:space-between; }
    .ticket-resultado-corpo { padding:var(--space-4) var(--space-6); }
    @media(max-width:576px) { .tipo-grid{grid-template-columns:repeat(2,1fr);} .portal-nav{display:none;} .form-card-corpo{padding:var(--space-4);} }
  </style>
</head>
<body>

<!-- Topbar -->
<nav class="portal-topbar">
  <div class="portal-topbar-inner">
    <a class="portal-logo" href="index.php">
      <div class="portal-logo-icon">R</div>
      <div>
        <div class="portal-logo-nome"><?= APP_NOME ?></div>
        <div class="portal-logo-sub">Portal do Estudante</div>
      </div>
    </a>
    <div class="portal-nav">
      <a href="?aba=registar" class="<?= $abaActiva==='registar'?'activo':'' ?>"><i class="bi bi-pencil-square"></i> Registar</a>
      <a href="?aba=consultar" class="<?= $abaActiva==='consultar'?'activo':'' ?>"><i class="bi bi-search"></i> Consultar</a>
      <a href="<?= Url::publico('login.php') ?>"><i class="bi bi-person-lock"></i> Staff</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="portal-hero">
  <h1><i class="bi bi-clipboard-check me-2"></i>Portal do Estudante</h1>
  <p>Registe ocorrências sobre notas académicas ou consulte o estado dos seus registos anteriores.</p>
</div>

<!-- Abas -->
<div class="aba-switcher">
  <button class="aba-btn <?= $abaActiva==='registar'?'activa':'' ?>" onclick="trocarAba('registar',this)">
    <i class="bi bi-plus-circle"></i> Registar Ocorrência
  </button>
  <button class="aba-btn <?= $abaActiva==='consultar'?'activa':'' ?>" onclick="trocarAba('consultar',this)">
    <i class="bi bi-search"></i> Consultar Estado
  </button>
</div>

<!-- Conteúdo -->
<div style="padding:28px 0 60px">
  <div class="container" style="max-width:840px">

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="alerta alerta-<?= $flash['tipo']==='sucesso'?'sucesso':'erro' ?> fade-in mb-4" id="flashMsg">
      <i class="bi <?= $flash['tipo']==='sucesso'?'bi-check-circle-fill':'bi-exclamation-triangle-fill' ?>" style="font-size:16px;flex-shrink:0"></i>
      <span><?= $flash['msg'] ?></span>
      <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:18px;color:inherit">&times;</button>
    </div>
    <?php endif; ?>

    <!-- ═══ ABA: REGISTAR ═══ -->
    <div id="aba-registar" class="aba-painel" style="display:<?= $abaActiva==='registar'?'block':'none' ?>">
      <div class="form-card">
        <div class="form-card-topo">
          <div class="form-card-ico"><i class="bi bi-exclamation-triangle"></i></div>
          <div>
            <div style="font-weight:700;font-size:.95rem">Formulário de Registo de Ocorrência</div>
            <div style="font-size:.75rem;color:var(--texto-muted)">Preencha todos os campos obrigatórios (*)</div>
          </div>
        </div>
        <div class="form-card-corpo">
          <form method="POST" id="formSubmissao" autocomplete="off">
            <?= Sessao::campoCsrf() ?>
            <input type="hidden" name="_acao" value="submeter">

            <!-- Dados pessoais -->
            <div class="row g-3 mb-4">
              <div class="col-12">
                <label class="rotulo-campo"><i class="bi bi-person me-1"></i> Nome Completo *</label>
                <input type="text" name="estudante_nome" class="campo-input" required
                       placeholder="Nome completo do estudante"
                       value="<?= Sanitizar::html($formValues['estudante_nome']??'') ?>">
              </div>
              <div class="col-sm-4">
                <label class="rotulo-campo"><i class="bi bi-hash me-1"></i> Nº Estudante *</label>
                <input type="text" name="estudante_numero" class="campo-input" required
                       placeholder="Ex: 20240001"
                       value="<?= Sanitizar::html($formValues['estudante_numero']??'') ?>">
              </div>
              <div class="col-sm-4">
                <label class="rotulo-campo"><i class="bi bi-telephone me-1"></i> Telefone *</label>
                <input type="text" name="estudante_telefone" class="campo-input" required
                       placeholder="+244 9xx xxx xxx"
                       value="<?= Sanitizar::html($formValues['estudante_telefone']??'') ?>">
              </div>
              <div class="col-sm-4">
                <label class="rotulo-campo"><i class="bi bi-envelope me-1"></i> Email *</label>
                <input type="email" name="estudante_email" class="campo-input" required
                       placeholder="email@estudante.ao"
                       value="<?= Sanitizar::html($formValues['estudante_email']??'') ?>">
              </div>
            </div>

            <!-- Contexto académico -->
            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <label class="rotulo-campo"><i class="bi bi-mortarboard me-1"></i> Curso *</label>
                <select name="curso_id" id="sCurso" class="campo-input" required onchange="onCursoChange()">
                  <option value="">-- Seleccionar --</option>
                  <?php foreach ($cursos as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= ($formValues['curso_id']??0)==$c['id']?'selected':'' ?>><?= Sanitizar::html($c['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="rotulo-campo"><i class="bi bi-people me-1"></i> Turma *</label>
                <select name="turma_id" id="sTurma" class="campo-input" required>
                  <option value="">-- Seleccionar curso primeiro --</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="rotulo-campo"><i class="bi bi-calendar-range me-1"></i> Semestre *</label>
                <select name="semestre_id" id="sSemestre" class="campo-input" required onchange="onSemestreChange()">
                  <option value="">-- Seleccionar --</option>
                  <?php foreach ($semestres as $s): ?>
                  <option value="<?= $s['id'] ?>" data-num="<?= $s['numero'] ?>"><?= Sanitizar::html($s['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="rotulo-campo"><i class="bi bi-book me-1"></i> Disciplina *</label>
                <select name="disciplina_id" id="sDisciplina" class="campo-input" required onchange="onDisciplinaChange()">
                  <option value="">-- Seleccionar semestre e turma --</option>
                </select>
              </div>
            </div>

            <!-- Professor (auto) -->
            <div class="mb-4">
              <label class="rotulo-campo"><i class="bi bi-person-badge me-1"></i> Professor</label>
              <div class="prof-display" id="profDisplay">
                <i class="bi bi-person-circle" style="font-size:18px;opacity:.5"></i>
                <span id="profNome">Preenchido automaticamente ao seleccionar a disciplina</span>
              </div>
            </div>

            <!-- Tipo avaliação -->
            <div class="mb-4">
              <label class="rotulo-campo"><i class="bi bi-journal-check me-1"></i> Tipo de Avaliação *</label>
              <div class="tipo-grid">
                <?php foreach (Etiqueta::todosTipos() as $k => $v): ?>
                <div>
                  <input type="radio" class="tipo-radio" name="tipo_avaliacao" id="tipo_<?= $k ?>" value="<?= $k ?>"
                         <?= ($formValues['tipo_avaliacao']??'')===$k?'checked':'' ?>>
                  <label class="tipo-etiqueta" for="tipo_<?= $k ?>"><?= $v ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Descrição -->
            <div class="mb-4">
              <label class="rotulo-campo"><i class="bi bi-chat-text me-1"></i> Descrição da Ocorrência * <small class="text-muted">(mín. 15 caracteres)</small></label>
              <textarea name="descricao" class="campo-input" rows="5" required
                        placeholder="Descreva detalhadamente o problema encontrado com a sua nota..."><?= Sanitizar::html($formValues['descricao']??'') ?></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <p style="font-size:12px;color:var(--texto-muted);margin:0">
                <i class="bi bi-info-circle me-1"></i>Após a submissão, receberá um <strong>código de ticket</strong> para consultar o estado.
              </p>
              <button type="submit" class="btn-base btn-primario">
                <i class="bi bi-send"></i> Submeter Ocorrência
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ═══ ABA: CONSULTAR ═══ -->
    <div id="aba-consultar" class="aba-painel" style="display:<?= $abaActiva==='consultar'?'block':'none' ?>">
      <div class="form-card mb-4">
        <div class="form-card-topo">
          <div class="form-card-ico"><i class="bi bi-search"></i></div>
          <div>
            <div style="font-weight:700;font-size:.95rem">Consultar Estado da Ocorrência</div>
            <div style="font-size:.75rem;color:var(--texto-muted)">Pesquise pelo código do ticket ou pelo seu nome</div>
          </div>
        </div>
        <div class="form-card-corpo">
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="rotulo-campo">Código do Ticket</label>
              <div style="position:relative">
                <i class="bi bi-ticket-perforated" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--texto-muted);font-size:16px"></i>
                <input type="text" id="inputTicket" class="campo-input" style="padding-left:40px"
                       placeholder="RN-2026-000001" value="<?= Sanitizar::html($_GET['ticket']??'') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <label class="rotulo-campo">Ou pesquise pelo Nome</label>
              <div style="position:relative">
                <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--texto-muted);font-size:16px"></i>
                <input type="text" id="inputNome" class="campo-input" style="padding-left:40px"
                       placeholder="Nome completo..." value="<?= Sanitizar::html($_GET['q']??'') ?>">
              </div>
            </div>
          </div>
          <button class="btn-base btn-primario w-100" onclick="pesquisar()">
            <i class="bi bi-search me-2"></i>Pesquisar
          </button>
        </div>
      </div>
      <!-- Resultados -->
      <div id="resultados"></div>
    </div>

  </div>
</div>

<script>
/* ── Dados JSON ─────────────────────────────────────────── */
const TODAS_DISCS  = <?= $discsJson ?>;
const TODAS_TURMAS = <?= $turmasJson ?>;
const TODOS_SEMS   = <?= $semsJson ?>;

/* ── Trocar aba ─────────────────────────────────────────── */
function trocarAba(aba, btn) {
    document.querySelectorAll('.aba-painel').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.aba-btn').forEach(el => el.classList.remove('activa'));
    document.getElementById('aba-' + aba).style.display = 'block';
    btn.classList.add('activa');
}

/* ── Ao mudar curso: carregar turmas ────────────────────── */
function onCursoChange() {
    var cursoId = parseInt(document.getElementById('sCurso').value) || 0;
    var sel = document.getElementById('sTurma');
    sel.innerHTML = '<option value="">-- Seleccionar turma --</option>';
    if (!cursoId) return;
    TODAS_TURMAS.filter(t => t.curso_id === cursoId).forEach(t => {
        var opt = new Option(t.codigo + ' (' + t.label + ')', t.id);
        sel.appendChild(opt);
    });
    carregarDisciplinas();
}

/* ── Ao mudar semestre: recarregar disciplinas ──────────── */
function onSemestreChange() { carregarDisciplinas(); }

/* ── Carregar disciplinas filtradas ─────────────────────── */
function carregarDisciplinas() {
    var cursoId = parseInt(document.getElementById('sCurso').value)    || 0;
    var semEl   = document.getElementById('sSemestre');
    var semNum  = semEl.selectedIndex > 0
        ? parseInt(semEl.options[semEl.selectedIndex].dataset.num) || 0 : 0;

    var sel  = document.getElementById('sDisciplina');
    sel.innerHTML = '<option value="">-- Seleccionar disciplina --</option>';
    resetProf();
    if (!cursoId || !semNum) return;

    TODAS_DISCS.filter(d => d.curso_id === cursoId && d.sem_num === semNum).forEach(d => {
        var opt = new Option(d.nome + (d.codigo ? ' (' + d.codigo + ')' : ''), d.id);
        opt.dataset.prof = d.prof;
        sel.appendChild(opt);
    });
}

/* ── Ao mudar disciplina: mostrar professor ─────────────── */
function onDisciplinaChange() {
    var sel  = document.getElementById('sDisciplina');
    var opt  = sel.selectedOptions[0];
    var disp = document.getElementById('profDisplay');
    var nome = document.getElementById('profNome');
    if (opt && opt.dataset.prof) {
        nome.textContent = opt.dataset.prof;
        disp.classList.add('preenchido');
    } else {
        resetProf();
    }
}
function resetProf() {
    document.getElementById('profNome').textContent = 'Preenchido automaticamente ao seleccionar a disciplina';
    document.getElementById('profDisplay').classList.remove('preenchido');
}

/* ── Pré-preencher em caso de erro ──────────────────────── */
<?php if ($formValues): ?>
onCursoChange();
setTimeout(function() {
    document.getElementById('sSemestre').value = <?= (int)($formValues['semestre_id']??0) ?>;
    onSemestreChange();
    setTimeout(function() {
        document.getElementById('sTurma').value      = <?= (int)($formValues['turma_id']??0) ?>;
        document.getElementById('sDisciplina').value = <?= (int)($formValues['disciplina_id']??0) ?>;
        onDisciplinaChange();
    }, 80);
}, 80);
<?php endif; ?>

/* ── Pesquisa de ocorrências (AJAX) ─────────────────────── */
function pesquisar() {
    var ticket = document.getElementById('inputTicket').value.trim();
    var nome   = document.getElementById('inputNome').value.trim();
    var resDiv = document.getElementById('resultados');

    if (!ticket && nome.length < 2) {
        alert('Introduza um código de ticket ou pelo menos 2 caracteres do nome.');
        return;
    }

    resDiv.innerHTML = '<div style="text-align:center;padding:30px"><div class="spinner-border text-primary" role="status"></div><p style="margin-top:10px;color:var(--texto-muted);font-size:13px">A pesquisar...</p></div>';

    var params = new URLSearchParams();
    if (ticket) params.set('ticket', ticket);
    else         params.set('q', nome);

    fetch('<?= \App\Helpers\Url::publico('pesquisar.php') ?>?' + params.toString())
        .then(r => r.json())
        .then(data => renderResultados(data, ticket || nome))
        .catch(() => {
            resDiv.innerHTML = '<div class="alerta alerta-erro"><i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i><span>Erro ao pesquisar. Tente novamente.</span></div>';
        });
}

function renderResultados(dados, termo) {
    var div = document.getElementById('resultados');
    if (!dados.length) {
        div.innerHTML = '<div style="text-align:center;padding:40px;color:var(--texto-muted)"><i class="bi bi-inbox" style="font-size:3rem;opacity:.3;display:block;margin-bottom:10px"></i><p>Nenhuma ocorrência encontrada para <strong>' + escHtml(termo) + '</strong>.</p><small>Verifique se o código ou nome está correcto.</small></div>';
        return;
    }

    var estadoLabel = {submetida:'Submetida',em_analise:'Em Análise',encaminhada_professor:'Encaminhada ao Professor',respondida_professor:'Respondida pelo Professor',em_revisao_coordenacao:'Em Revisão',resolvida:'Resolvida',encerrada:'Encerrada',cancelada:'Cancelada'};
    var estadoBadge = {submetida:'badge-submetida',em_analise:'badge-analise',encaminhada_professor:'badge-encaminhada',respondida_professor:'badge-respondida',em_revisao_coordenacao:'badge-revisao',resolvida:'badge-resolvida',encerrada:'badge-encerrada',cancelada:'badge-cancelada'};

    var html = '<div style="font-size:12.5px;color:var(--texto-muted);margin-bottom:14px"><i class="bi bi-info-circle me-1"></i>' + dados.length + ' resultado(s) para <strong>' + escHtml(termo) + '</strong></div>';

    dados.forEach(function(o) {
        html += '<div class="ticket-resultado mb-3">';
        html += '<div class="ticket-resultado-topo">';
        html += '<span style="font-family:\'JetBrains Mono\',monospace;font-weight:700;font-size:14px">' + escHtml(o.ticket) + '</span>';
        html += '<span class="badge-estado ' + (estadoBadge[o.estado]||'badge-secundario') + '">' + (estadoLabel[o.estado]||o.estado) + '</span>';
        html += '</div>';
        html += '<div class="ticket-resultado-corpo">';
        html += '<div class="row g-2">';
        html += '<div class="col-sm-6"><small style="color:var(--texto-muted);display:block;font-size:11px">Estudante</small><strong style="font-size:13.5px">' + escHtml(o.estudante_nome) + '</strong></div>';
        html += '<div class="col-sm-3"><small style="color:var(--texto-muted);display:block;font-size:11px">Submetida em</small><span style="font-size:13px">' + new Date(o.criado_em).toLocaleDateString('pt-PT') + '</span></div>';
        html += '<div class="col-sm-3"><small style="color:var(--texto-muted);display:block;font-size:11px">Última actualização</small><span style="font-size:13px">' + new Date(o.atualizado_em).toLocaleDateString('pt-PT') + '</span></div>';
        if (o.observacao_oficial) {
            html += '<div class="col-12 mt-2"><div class="bloco-oficial"><strong>Observação Oficial:</strong> ' + escHtml(o.observacao_oficial) + '</div></div>';
        }
        // Feedback UI
        if (o.estado === 'resolvida' && !o.feedback) {
            html += '<div class="col-12 mt-3 p-3 bg-white rounded border border-light">';
            html += '<label class="rotulo-campo">Deixe seu feedback:</label>';
            html += '<textarea class="campo-input" id="feedback_'+o.id+'" rows="2"></textarea>';
            html += '<button onclick="enviarFeedback('+o.id+')" class="btn-base btn-primario mt-2">Enviar Feedback</button>';
            html += '</div>';
        }
        html += '</div></div></div>';
    });

    div.innerHTML = html;
}

function enviarFeedback(id) {
    var fb = document.getElementById('feedback_'+id).value;
    var token = document.querySelector('input[name="_token"]').value; // Obtém o token da página
    if(!fb) { alert('Por favor, escreva o seu feedback.'); return; }
    
    fetch('<?= \App\Helpers\Url::publico('api/feedback.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        // Adiciona o token aqui
        body: 'id='+id+'&feedback='+encodeURIComponent(fb)+'&_token='+token
    })
    .then(r => r.json())
    .then(data => {
        if(data.sucesso) { alert('Feedback enviado com sucesso!'); pesquisar(); }
        else { alert('Erro: ' + (data.erro || 'Falha ao gravar')); }
    });
}

// Polling for updates (every 30 seconds)
setInterval(function() {
    var ticket = document.getElementById('inputTicket').value.trim();
    if (ticket) pesquisar();
}, 30000);

function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* Auto-pesquisar se vier parâmetro na URL */
<?php if ($abaActiva === 'consultar' && (!empty($_GET['ticket']) || !empty($_GET['q']))): ?>
window.addEventListener('load', function() { pesquisar(); });
<?php endif; ?>

/* Auto-fechar flash */
(function() {
    var m = document.getElementById('flashMsg');
    if (m) setTimeout(function() { m.style.transition='opacity .4s'; m.style.opacity='0'; setTimeout(function(){m.remove();},400); }, 8000);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require APP_DIR . '/views/shared/tema_publico.php'; ?>
</body>
</html>
