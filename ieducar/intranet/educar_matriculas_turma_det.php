<?php

require_once 'include/clsBase.inc.php';
require_once 'include/clsDetalhe.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';

class clsIndexBase extends clsBase
{
    public function Formular()
    {
        $this->SetTitulo("{$this->_instituicao} i-Educar - Matriculas Turma");
        $this->processoAp = '659';
        $this->addEstilo('localizacaoSistema');
    }
}

class indice extends clsDetalhe
{
    /**
     * Titulo no topo da pagina
     *
     * @var int
     */
    public $titulo;

    public $ref_cod_matricula;

    public $ref_cod_turma;

    public $ref_usuario_exc;

    public $ref_usuario_cad;

    public $data_cadastro;

    public $data_exclusao;

    public $ativo;

    public $ref_cod_serie;

    public $ref_cod_escola;

    public function Gerar()
    {
        $this->titulo = 'Matrículas Turma - Detalhe';

        $this->ref_cod_turma = $_GET['ref_cod_turma'];

        $obj_turma = new clsPmieducarTurma($this->ref_cod_turma);
        $det_turma = $obj_turma->detalhe();
        $nm_turma = $det_turma['nm_turma'];
        $ref_ref_cod_serie = $det_turma['ref_ref_cod_serie'];
        $ref_ref_cod_escola = $det_turma['ref_ref_cod_escola'];
        $ref_cod_curso = $det_turma['ref_cod_curso'];

        if ($ref_ref_cod_serie) {
            // busca o ano em q a escola esta em andamento
            $obj_ano_letivo = new clsPmieducarEscolaAnoLetivo();
            $lst_ano_letivo = $obj_ano_letivo->lista($ref_ref_cod_escola, null, null, null, 1, null, null, null, null, 1);

            if (is_array($lst_ano_letivo)) {
                $det_ano_letivo = array_shift($lst_ano_letivo);
                $ano_letivo = $det_ano_letivo['ano'];
            } else {
                $this->mensagem = 'Não foi possível encontrar o Ano Letivo.';

                return false;
            }
        }

        if ($ano_letivo || !$ref_ref_cod_serie) {
            $obj_matriculas_turma = new clsPmieducarMatriculaTurma();
            $lst_matriculas_turma = $obj_matriculas_turma->lista(null, $this->ref_cod_turma, null, null, null, null, null, null, 1, null, null, null, null, null, false, null, [1, 2, 3], null, null, $ano_letivo, null, false, null, 1, true);

            if (is_array($lst_matriculas_turma)) {
                $qtd_alunos = count($lst_matriculas_turma);

                foreach ($lst_matriculas_turma as $key => $matricula) {
                    $obj_matricula = new clsPmieducarMatricula($matricula['ref_cod_matricula']);
                    $det_matricula = $obj_matricula->detalhe();

                    $obj_aluno = new clsPmieducarAluno();
                    $lst_aluno = $obj_aluno->lista($det_matricula['ref_cod_aluno']);
                    $det_aluno = array_shift($lst_aluno);
                    $nm_alunos[] = $det_aluno['nome_aluno'];
                }

                $registro = array_shift($lst_matriculas_turma);

            } else {

                $obj_turma = new clsPmieducarTurma();
                $lst_turma = $obj_turma->lista($this->ref_cod_turma);

                if (is_array($lst_turma)) {
                    $registro = array_shift($lst_turma);
                }
            }

        } else {

            $obj_turma = new clsPmieducarTurma();
            $lst_turma = $obj_turma->lista($this->ref_cod_turma);

            if (is_array($lst_turma)) {
                $registro = array_shift($lst_turma);
            }
        }

        if (empty($registro)) {
            header('location: educar_matriculas_turma_lst.php');
            die();
        }

        $obj_ref_cod_serie = new clsPmieducarSerie($registro['ref_ref_cod_serie']);
        $det_ref_cod_serie = $obj_ref_cod_serie->detalhe();
        $nm_serie = $det_ref_cod_serie['nm_serie'];

        $obj_ref_cod_curso = new clsPmieducarCurso($registro['ref_cod_curso']);
        $det_ref_cod_curso = $obj_ref_cod_curso->detalhe();
        $registro['ref_cod_curso'] = $det_ref_cod_curso['nm_curso'];

        $obj_cod_instituicao = new clsPmieducarInstituicao($registro['ref_cod_instituicao']);
        $obj_cod_instituicao_det = $obj_cod_instituicao->detalhe();
        $registro['ref_cod_instituicao'] = $obj_cod_instituicao_det['nm_instituicao'];

        $obj_ref_cod_escola = new clsPmieducarEscola($registro['ref_ref_cod_escola']);
        $det_ref_cod_escola = $obj_ref_cod_escola->detalhe();
        $nm_escola = $det_ref_cod_escola['nome'];

        $obj_permissoes = new clsPermissoes();
        $nivel_usuario = $obj_permissoes->nivel_acesso($this->pessoa_logada);

        if ($nivel_usuario == 1) {
            if ($registro['ref_cod_instituicao']) {
                $this->addDetalhe(['Instituição', "{$registro['ref_cod_instituicao']}"]);
            }
        }

        if ($nivel_usuario == 1 || $nivel_usuario == 2) {
            if ($nm_escola) {
                $this->addDetalhe(['Escola', "{$nm_escola}"]);
            }
        }

        if ($registro['ref_cod_curso']) {
            $this->addDetalhe(['Curso', "{$registro['ref_cod_curso']}"]);
        }

        if ($nm_serie) {
            $this->addDetalhe(['Série', "{$nm_serie}"]);
        }

        if ($nm_turma) {
            $this->addDetalhe(['Turma', "{$nm_turma}"]);
        }

        if ($qtd_alunos) {
            $this->addDetalhe(['Qtd Alunos Matriculados', "{$qtd_alunos}"]);
        }

        if (is_array($nm_alunos)) {
            sort($nm_alunos);

            $tabela = '<table>
                           <tr align=center>
                               <td bgcolor=#ccdce6><b>Nome</b></td>
                           </tr>';

            reset($nm_alunos);

            for ($i = 0; $i < count($nm_alunos); $i++) {
                if (($i % 2) == 0) {
                    $color = ' bgcolor=#f5f9fd ';
                } else {
                    $color = ' bgcolor=#FFFFFF ';
                }

                $tabela .= "<tr>
                                <td align=center {$color} align=left>{$nm_alunos[$i]}</td>
                            </tr>";
            }

            $tabela .= '</table>';
        }

        if ($tabela) {
            $this->addDetalhe(['Alunos Matriculados', "{$tabela}"]);
        }

        if ($obj_permissoes->permissao_cadastra(659, $this->pessoa_logada, 7)) {
            $this->url_editar = "educar_matriculas_turma_cad.php?ref_cod_turma={$this->ref_cod_turma}";
        }

        $this->url_cancelar = 'educar_matriculas_turma_lst.php';
        $this->largura = '100%';

        $this->breadcrumb('Detalhe das matrículas da turma', [
            'educar_index.php' => 'Escola',
        ]);
    }
}

$pagina = new clsIndexBase();
$miolo = new indice();

$pagina->addForm($miolo);
$pagina->MakeAll();
