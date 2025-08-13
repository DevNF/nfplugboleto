<?php
namespace NFService\PlugBoleto;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API Tecnospeed
 *
 * @category  NFService
 * @package   NFService\PlugBoleto\Tools
 * @author    Diego Almeida <diego.feres82 at gmail dot com>
 * @copyright 2020 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, cnpj, ambiente(produção ou homologação)
     *
     * @var array
     */
    private $config = [
        'cnpj-sh' => '',
        'token-sh' => '',
        'cnpj-cedente' => '',
        'production' => false,
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Metodo contrutor da classe
     *
     * @param boolean $isProduction Define se o ambiente é produção
     */
    public function __construct(bool $isProduction = true)
    {
        $this->setProduction($isProduction);
    }

    /**
     * Define se a classe deve se comunicar com API de Produção ou com a API de Homologação
     *
     * @param bool $isProduction Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setProduction(bool $isProduction) :void
    {
        $this->config['production'] = $isProduction;
    }

    /**
     * Função responsável por setar o CNPJ a ser utilizado na comunicação com a API do PlugBoleto
     *
     * @param string $cnpj CNPJ da SofterHouse
     *
     * @access public
     * @return void
     */
    public function setCnpj(string $cnpj) :void
    {
        $this->config['cnpj-sh'] = $cnpj;
    }

    /**
     * Função responsável por setar o CNPJ do cedente a ser utilizado na comunicação com a API do PlugBoleto
     *
     * @param string $cnpj CNPJ da SofterHouse
     *
     * @access public
     * @return void
     */
    public function setCnpjCedente(string $cnpj) :void
    {
        $this->config['cnpj-cedente'] = $cnpj;
    }

    /**
     * Função responsável por setar o token a ser utilizada na comunicação com a API do PlugBoleto
     *
     * @param string $token Token da SofterHouse
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token-sh'] = $token;
    }

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Retorna se o ambiente setado é produção ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getProduction() : bool
    {
        return $this->config['production'];
    }

    /**
     * Recupera o cnpj setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getCnpj() :string
    {
        return $this->config['cnpj-sh'];
    }

    /**
     * Recupera o cnpj do cedente setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getCnpjCedente() :string
    {
        return $this->config['cnpj-cedente'];
    }

    /**
     * Recupera o token setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getToken() :string
    {
        return $this->config['token-sh'];
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) : void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'cnpj-sh: '.$this->config['cnpj-sh'],
            'token-sh: '.$this->config['token-sh'],
            'cnpj-cedente: '.$this->config['cnpj-cedente'],
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }

        return $headers;
    }

    /**
     * Busca um cedente na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function buscaCedente(array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            $dados = $this->get('cedentes', $params, []);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra um novo cedente na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function cadastraCedente(array $dados, array $params = []): array
    {
        try {
            $dados = $this->post('cedentes', $dados, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Atualiza um cedente específico
     *
     * @access public
     * @return array
     */
    public function atualizaCedente($id, array $dados, array $params = []): array
    {
        $cnpj = $dados['CedenteCPFCNPJ'];

        try {
            $dados = $this->put('cedentes/'.$id, $dados, $params, ['cnpj-cedente: '.$cnpj]);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Busca as contas de um cedente na tecnospeed
     *
     * @access public
     * @return array
     */
    public function buscaContas(array $params = [])
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            $dados = $this->get('cedentes/contas', $params);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma nova conta na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function cadastraConta(array $dados, array $params = []): array
    {
        try {
            $dados['ContaTipo'] = 'CORRENTE';
            $dados['ContaValidacaoAtiva'] = false;
            $dados['ContaImpressaoAtualizada'] = false;

            $dados = $this->post('cedentes/contas', $dados, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Atualiza uma conta na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function atualizaConta(int $id, array $dados, array $params = []): array
    {
        try {
            $dados['ContaTipo'] = 'CORRENTE';
            $dados['ContaValidacaoAtiva'] = false;
            $dados['ContaImpressaoAtualizada'] = false;

            $dados = $this->put('cedentes/contas/'.$id, $dados, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Deleta uma conta na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function deletaConta(int $id, array $params = []): array
    {
        try {
            $dados = $this->delete('cedentes/contas/'.$id, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Busca os convênios da conta de um cedente na tecnospeed
     *
     * @access public
     * @return array
     */
    public function buscaConvenios(array $params = [])
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            $dados = $this->get('cedentes/contas/convenios', $params);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra um novo convênio na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function cadastraConvenio(array $dados, array $params = []): array
    {
        try {
            $dados = $this->post('cedentes/contas/convenios', $dados, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Atualiza um novo convênio na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function atualizaConvenio(int $id, array $dados, array $params = []): array
    {
        try {
            $dados = $this->put('cedentes/contas/convenios/'.$id, $dados, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Deleta um convênio na Tecnospeed
     *
     * @access public
     * @return array
     */
    public function deletaConvenio(int $id, array $params = []): array
    {
        try {
            $dados = $this->delete('cedentes/contas/convenios/'.$id, $params);

            if ($dados["body"]->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função reponsável por emitir um boleto pela tecnospeed
     *
     * @access public
     * @return array
     */
    public function emiteBoletos(array $dados, array $params = []): array
    {
        try {
            //Filtra os campos de todos os boletos
            foreach ($dados as $key => $boleto) {
                //Seta o valor padrão para o Local de Pagamento
                $dados[$key]['TituloLocalPagamento'] = 'Pagável em qualquer banco até o vencimento';
                //Caso o banco seja o 089, seta o valor padrão como 1 para a modalidade do titulo
                if (isset($dados[$key]['CedenteContaCodigoBanco']) && $dados[$key]['CedenteContaCodigoBanco'] == '089') {
                    $dados[$key]['TituloModalidade'] = '1';
                }
            }

            $dados = $this->post('boletos/lote', $dados, $params);

            //Paga os ids dos boletos que obtiveram sucesso para verificar se foram realmente emitidos
            if (isset($dados['body']->_dados->_sucesso)) {
                $ids = array_map(function($item) {
                    return $item->idintegracao;
                }, $dados['body']->_dados->_sucesso);
            }

            if (isset($ids) && !empty($ids)) {
                //Seta o limite de registro na requisição baseado na quantidade de ids para não ter que fazer mais de uma requisição
                $params = [
                    [
                        'name' => 'limit',
                        'value' => count($ids)
                    ]
                ];
                //Seta o params para filtro pelo os ids
                foreach ($ids as $id) {
                    $params[] = [
                        'name' => 'idintegracao',
                        'value' => $id
                    ];
                }
                //Espera 4 segundos para dar tempo de processar os boletos
                sleep(4);
                $validate = $this->get('/boletos', $params);
                //Caso a consulta tenha sido bem succedida organiza os dados para serem usados depois
                if ($validate['body']->_status != 'erro') {
                    //Organiza um arrau com as informações necessárias
                    $result = array_map(function($item) {
                        return ['idintegracao' => $item->IdIntegracao, 'situacao' => $item->situacao, 'motivo' => isset($item->motivo) ? $item->motivo : null, 'TituloLinhaDigitavel' => isset($item->TituloLinhaDigitavel) ? $item->TituloLinhaDigitavel : null, 'TituloCodigoBarras' => isset($item->TituloCodigoBarras) ? $item->TituloCodigoBarras : null, 'TituloNumeroDocumento' => isset($item->TituloNumeroDocumento) ? $item->TituloNumeroDocumento : null];
                    }, $validate['body']->_dados);
                    $ids = [];
                    //Organiza o array utilizando o idintegracao com chave
                    foreach ($result as $key => $value) {
                        $ids[$value['idintegracao']] = $value;
                    }
                }
            }

            $return['status'] = true;
            $return['success'] = [];
            $return['errors'] = [];
            //Se não hover nenhum sucesso na geração de boletos, seta a flag de status como false
            if ($dados["body"]->_status == 'erro') {
                $return['status'] = false;
            }

            if (isset($dados['body']->_dados->_sucesso)) {
                //Pega os boletos que foram salvos e emitidos com sucesso
                $return['success'] = array_filter($dados['body']->_dados->_sucesso, function($item) use($ids) {
                    return !in_array($ids[$item->idintegracao]['situacao'], ['FALHA', 'REJEITADO']);
                });

                foreach ($return['success'] as $key => $boleto) {
                    $return['success'][$key]->situacao = $ids[$boleto->idintegracao]['situacao'];
                    $return['success'][$key]->TituloLinhaDigitavel = $ids[$boleto->idintegracao]['TituloLinhaDigitavel'];
                    $return['success'][$key]->TituloCodigoBarras = $ids[$boleto->idintegracao]['TituloCodigoBarras'];
                    $return['success'][$key]->TituloNumeroDocumento = $ids[$boleto->idintegracao]['TituloNumeroDocumento'];
                }
            }

            if (isset($dados['body']->_dados->_falha)) {
                //Pega os boletos  que não foram salvos
                $return['errors'] = array_map(function($item) {
                    if (isset($item->TituloNossoNumero) && isset($item->TituloNumeroDocumento)) {
                        return (object) ['TituloNossoNumero' => $item->TituloNossoNumero, 'TituloNumeroDocumento' => $item->TituloNumeroDocumento, 'situacao' => 'FALHA', 'motivo' => json_encode($item->_erros)];
                    } else if (isset($item->_erro) && isset($item->_dados)) {
                        return (object) ['situacao' => 'FALHA', 'motivo' => json_encode($item->_erro->erros)];
                    }
                }, $dados['body']->_dados->_falha);
            } else if (isset($dados['body']->_dados->_erro)) {
                $return['error'] = $dados['body']->_dados->_erro;
            }

            if (isset($dados['body']->_dados->_sucesso)) {
                //Pega os boletos que foram salvos mas não foram emitidos por alguma falha ou rejeição
                $naoEmitidos = array_filter($dados['body']->_dados->_sucesso, function($item) use($ids) {
                    return in_array($ids[$item->idintegracao]['situacao'], ['FALHA', 'REJEITADO']);
                });

                //caso exista boletos não emitidos, faz um merge com os mesmos na variável de boletos com erros
                if (!empty($naoEmitidos)) {
                    foreach($naoEmitidos as $key => $value) {
                        $naoEmitidos[$key]->situacao = 'FALHA';
                        $naoEmitidos[$key]->motivo = $ids[$naoEmitidos[$key]->idintegracao]['motivo'];
                        if (isset($ids[$naoEmitidos[$key]->idintegracao]['TituloNossoNumero'])) {
                            $naoEmitidos[$key]->TituloNossoNumero = $ids[$naoEmitidos[$key]->idintegracao]['TituloNossoNumero'];
                        }
                        if (isset($ids[$naoEmitidos[$key]->idintegracao]['TituloNumeroDocumento'])) {
                            $naoEmitidos[$key]->TituloNumeroDocumento = $ids[$naoEmitidos[$key]->idintegracao]['TituloNumeroDocumento'];
                        }
                    }
                    $return['errors'] = array_merge($return['errors'], $naoEmitidos);
                }
            }
            //retirna os dados
            return $return;
        } catch (Exception $error) {
            //Exception para caso exista erro em código ou alguma requisição
            throw new Exception($error, 1);
        }
    }

    /**
     * Consulta os boletos de um cedente na tecnospeed
     *
     * @access public
     * @return array
     */
    public function consultaBoletos(array $params = []): array
    {
        try {
            $hasLimit = array_filter($params, function($item) {
                return $item['name'] === 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            if (empty($hasLimit)) {
                $params[] = [
                    'name' => 'limit',
                    'value' => 200
                ];
            }

            $dados = $this->get('boletos', $params);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Descarta os boletos de um cedente na tecnospeed
     *
     * @access public
     * @return array
     */
    public function descartaBoletos(array $dados, array $params = []): array
    {
        if (!isset($dados) || empty($dados)) {
            throw new Exception("É necessário informar o idIntegracao de pelo menos 1 (um) boleto para o descarte", 1);
        }
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            $dados = $this->post('boletos/descarta/lote', $dados, $params);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Baixa os boletos de um cedente no banco e na tecnospeed
     *
     * @access public
     * @return array
     */
    public function baixaBoletos(array $dados, array $params = []): array
    {
        if (!isset($dados) || empty($dados)) {
            throw new Exception("É necessário informar o idIntegracao de pelo menos 1 (um) boleto para a baixa", 1);
        }
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            $dados = $this->post('boletos/baixa/lote', $dados, $params);

            if ($dados['body']->_status != 'erro') {
                return $dados;
            }

            $errors = array_map(function($item) {
                return $item->_erro;
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Imprime os boletos de um cedente na tecnospeed
     *
     * @param array $dados Array com ids dos boletos, ou contendo a personalização case for tipo 99
     * @param string $type Tipo de impressão
     *               0 - PDF normal (Padrão)
     *               1 - PDF carnê duplo (paisagem).
     *               2 - PDF carnê triplo (retrato).
     *               3 - PDF  dupla (retrato).
     *               4 - PDF normal (Com marca D'água).
     *               99 - PDF personalizada.
     *
     * @access public
     * @return string
     */
    public function imprimeBoletos(array $dados, string $type = "0", array $params = [])
    {
        if (!isset($dados) || empty($dados)) {
            throw new Exception("É necessário informar o idIntegracao de pelo menos 1 (um) boleto para a impressão", 1);
        }
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'limit';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'limit',
                'value' => 200
            ];

            if ($type == '99') {
                $dados = [
                    'Personalizacao' => $dados
                ];
            } else {
                $dados = [
                    'Boletos' => $dados
                ];
            }
            $dados['TipoImpressao'] = $type;

            $dados = $this->post('boletos/impressao/lote', $dados, $params);

            //Caso tenha sucesso na requisição busca o resultado pelo protocolo
            if ($dados['body']->_status != 'erro') {
                /**Consulta dez vezes pela impressão */
                $i = 10;
                $defaulDecode = $this->getDecode();
                $this->setDecode(false);
                //Repetição até que o protocolo tenha sido processado ou até que dê 10 tentativas
                while ($i > 0) {
                    /**Espera a impressão ser processada */
                    sleep(1);

                    $pdfContent = $this->get('boletos/impressao/lote/'.$dados['body']->_dados->protocolo, []);
                    //caso não exista a posição _status indica que houve sucesso e o retorno é um PDF, então retorna o mesmo
                    if (strpos($pdfContent['body'], '_status') === false && strpos($pdfContent['body'], 'erro') === false) {
                        $this->setDecode($defaulDecode);
                        return $pdfContent['body'];
                    }

                    $i--;
                }
                $this->setDecode($defaulDecode);
                $pdfContent['body'] = json_decode($pdfContent['body']);

                //Caso não tenha conseguido processar o PDF a tempo, retorna os erros e mensagens da ultima requisição de protocolo realizada
                $errors = [];
                if (isset($pdfContent['body']->_dados)) {
                    $errors = array_map(function($item) {
                        return $item->situacao;
                    }, $pdfContent['body']->_dados);
                }

                throw new Exception($pdfContent['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
            }

            //Caso a solicitação de PDF tenha dado erro, retorna os mesmos
            $errors = array_map(function($item) {
                if (isset($item->_erro)) {
                    return $item->_erro;
                } else {
                    return '';
                }
            }, $dados['body']->_dados);

            throw new Exception($dados['body']->_mensagem."\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Gera um arquivo remessa de boletos na Tecnospeed
     */
    public function geraArquivoRemessa(array $dados, array $params = []): array
    {
        if (!isset($dados) || empty($dados)) {
            throw new Exception("É necessário informar o idIntegracao de pelo menos 1 (um) boleto para gerar o arquivo remessa", 1);
        }

        try {
            $dados = $this->post('remessas/lote', $dados, $params);

            $return['status'] = true;
            //Se não hover nenhum sucesso na geração de boletos, seta a flag de status como false
            if ($dados["body"]->_status == 'erro') {
                $return['status'] = false;
            }

            $return['success'] = [];
            if (isset($dados['body']->_dados->_sucesso[0])) {
                $return['success'] = $dados['body']->_dados->_sucesso[0];
                $t = array_map(function ($item) {
                    return $item->idintegracao;
                }, $dados['body']->_dados->_sucesso[0]->titulos);
                $return['success']->titulos = $t;
            }
            if (empty($return['success'])) {
                $return['status'] = false;
            }

            $return['errors'] = [];
            if (isset($dados['body']->_dados->_falha)) {
                $return['errors'] = array_map(function ($item) {
                    return (object) ['idintegracao' => $item->idintegracao, 'error' => $item->_erro];
                }, $dados['body']->_dados->_falha);
            }

            return $return;
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Envia e processa um arquivo retorno
     */
    public function enviaRetorno(array $dados, int $cnab, array $params = []): array
    {
        if (!isset($dados['arquivo']) || empty($dados['arquivo'])) {
            throw new Exception("É obrigatório o envio do conteúdo do arquivo retorno", 1);
        }

        try {
            $dados = $this->post('retornos', $dados, $params);

            if ($dados['body']->_status != 'erro') {
                $protocolo = $dados['body']->_dados->protocolo;
                sleep(1);
                $dados = $this->get('retornos/'.$protocolo, $params);
                if (isset($dados['body']->_dados) && $dados['body']->_dados->situacao != 'PROCESSADO') {
                    if ($dados['body']->_dados->situacao == 'PROCESSANDO') {
                        $params = [['name' => 'limit', 'value' => $dados['body']->_dados->processados]];
                        $muitosRegistros = true;
                        $tentativas = 1;
                        while($muitosRegistros && $tentativas <= 70) {
                            sleep(2);
                            $dados = $this->get('retornos/'.$protocolo, $params);
                            if ($dados['body']->_dados->situacao != 'PROCESSANDO') {
                                $muitosRegistros = false;
                            }
                            $tentativas++;
                        }
                    }
                } else if ($dados['body']->_status == 'erro') {
                    throw new Exception($dados['body']->_mensagem, 1);
                }
            } else {
                throw new Exception($dados['body']->_mensagem, 1);
            }

            $boletos = [
                'titulos' => [],
                'naoConciliados' => array_map(function ($item){
                    return (object)['number' => $item->TituloNossoNumeroOriginal, 'number_doc' => $item->TituloNumeroDocumento, 'occurrences' => $item->Ocorrencias];
                }, $dados['body']->_dados->titulosNaoConciliados)
            ];
            $ids = array_map(function ($item) {
                return ['name' => 'idintegracao', 'value' => $item->idIntegracao];
            }, $dados['body']->_dados->titulos);

            foreach ($ids as $id) {
                $boletos['titulos'][$id['value']] = [];
            }

            $dadosTitulos = $dados['body']->_dados->titulos;

            $params = [[
                'name' => 'limit',
                'value' => count($dadosTitulos)
            ]];

            $params = array_merge($params, $ids);

            $dados = $this->consultaBoletos($params);

            \Log::driver('return-bank')->debug(
                'PlugBoleto consultaBoletos no enviaRetorno',
                [
                    'tags' => ['enviaRetorno'],
                    'protocolo' => $protocolo,
                    'dadosTitulos' => $dadosTitulos,
                    'params' => $params,
                    'dados' => $dados['body']
                ]
            );

            $dados = array_map(function ($item) {
                return $item;
            }, $dados['body']->_dados);

            foreach ($dados as $key => $boleto) {
                $dados[$key]->TituloMovimentos = array_map(function($item) {
                    return $item;
                }, $boleto->TituloMovimentos);

                foreach ($dados[$key]->TituloMovimentos as $key1 => $ocorrencia) {
                    $function = 'banco'.$boleto->CedenteCodigoBanco;
                    $boletos['titulos'][$boleto->IdIntegracao][$key1] = $this->$function($ocorrencia, $boleto, $cnab);
                    $boletos['titulos'][$boleto->IdIntegracao][$key1]['code'] = $ocorrencia->codigo;
                    $boletos['titulos'][$boleto->IdIntegracao][$key1]['date'] = dateBrToEn(explode(' ', $ocorrencia->data)[0]).' '.explode(' ', $ocorrencia->data)[1];
                }
            }

            return $boletos;
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função default para leitura de arquivo retorno
     */
    private function default($documento, $ocorrencia)
    {
        $action = [
            'action' => 'default',
            'data' => [],
            'message' => 'Movimento Duplicata '.$documento->TituloNumeroDocumento.' ('.$ocorrencia->mensagem.').'
        ];
        if (!empty($ocorrencia->ocorrencias)) {
            $occurrences = array_map(function ($msg) {
                return [
                    'code' => $msg->codigo,
                    'message' => $msg->mensagem
                ];
            }, $ocorrencia->ocorrencias);
            $action['occurrences'] = $occurrences;
        }
        return $action;
    }

    /**
     * Função responsavel por gerar o array a ser utilizado na baixa da duplicata
     *
     * @param object $documento objeto com as informações do boleto da Tecnospeed
     *
     * @access private
     * @return Array
     */
    private function payed($documento)
    {
        /**Caso o banco do boleto seja Itau, o calculo do juros é feito sob o Total pago pelo Sacado menos o Total do Titulo */
        $juros = (float)formatStringToFloat($documento->PagamentoValorPago) - (float)formatStringToFloat($documento->TituloValor);
        return [
            'action' => 'payed',
            'data' => [
                'doc_documento' => $documento->TituloNumeroDocumento,
                'ocurrence' => dateBrToEn(explode(" ", $documento->PagamentoData)[0]),
                'discount' => formatStringToFloat($documento->PagamentoValorDesconto),
                'value' => formatStringToFloat($documento->PagamentoValorPago),
                'others_receipts' => 0.00,
                'interest_delay' => 0.00,
                'interest_default' => $juros
            ]
        ];
    }

    /**
     * Função responsavel por gerar o array a ser utilizado na baixa que foi rejected
     *
     * @param object $documento objeto com as informações do boleto da Tecnospeed
     *
     * @access private
     * @return Array
     */
    private function rejected($documento, $ocorrencia)
    {
        $action = [
            'action' => 'rejected',
            'data' => [
                'number' => $documento->TituloNossoNumero
            ],
            'message' => 'Duplicata '.$documento->TituloNumeroDocumento.' rejeitada. ('.$ocorrencia->mensagem.').'
        ];
        if (!empty($ocorrencia->ocorrencias)) {
            $occurrences = array_map(function ($msg) {
                return [
                    'code' => $msg->codigo,
                    'message' => $msg->mensagem
                ];
            }, $ocorrencia->ocorrencias);
            $action['occurrences'] = $occurrences;
        }
        return $action;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco237($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '06':
                case '15':
                case '16':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                case '24':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '09':
                case '10':
                    if ($ocorrencia->codigo != '10') {
                        $actions = [
                            'action' => 'payed',
                            'data' => [
                                'number' => $documento->TituloNossoNumero
                            ]
                        ];
                    } else {
                        $actions = [
                            'action' => 'default',
                            'data' => []
                        ];
                    }
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '22':
                    $actions = [
                        'action' => 'removePayed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'valor' => formatStringToFloat($documento->PagamentoValorAbatimento),
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '17':
                case '45':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco341($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                case '64':
                case '73':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '07':
                case '08':
                case '10':
                case '59':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '32':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                case '15':
                case '16':
                case '17':
                case '18':
                case '60':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '08':
                case '23':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '10':
                case '32':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                case '15':
                case '16':
                case '17':
                case '18':
                case '60':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco001($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '05':
                case '06':
                case '07':
                case '08':
                case '15':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '10':
                case '20':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '17':
                case '23':
                case '45':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco033($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '07':
                case '08':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '10':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '17':
                case '23':
                case '25':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco748($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '15':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '10':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '17':
                case '23':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os datos do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco756($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '05':
                case '06':
                case '15':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '10':
                    $actions = [
                        'action' => 'canceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                case '17':
                case '23':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'canceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento Dados do Boleto vindo da TecnoSpeed
     *
     * @access public
     * @return array
     */
    public function banco104($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '01':
                    $actions = [
                        'action' => 'confirmed',
                        'dados' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '21':
                case '22':
                    $actions = $this->payed($documento);
                    break;
                case '02':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '05':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '06':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento Dados do Boleto vindo da TecnoSpeed
     *
     * @access public
     * @return array
     */
    public function banco422($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '15':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '40':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '15':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                case '40':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento Dados do Boleto vindo da TecnoSpeed
     *
     * @access public
     * @return array
     */
    public function banco021($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        } else {
            switch ($ocorrencia->codigo) {
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '06':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '09':
                    $actions = [
                        'action' => 'payed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                case '03':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Ação responsável por tratar as ocorrencias do arquivo retorno do banco especifico
     *
     * @param object $documento objeto com os dados do boleto, incluindo ocorrencias
     *
     * @return json
     * @access public
     **/
    public function banco089($ocorrencia, $documento, $cnab)
    {
        if ($cnab == '400') {
            switch ($ocorrencia->codigo) {
                case '05':
                case '06':
                case '15':
                case '16':
                case '17':
                    $actions = $this->payed($documento);
                    break;
                case '02':
                    $actions = [
                        'action' => 'confirmed',
                        'data' => [
                            'number' => $documento->TituloNossoNumero
                        ]
                    ];
                    break;
                case '03':
                case '30':
                    $actions = $this->rejected($documento, $ocorrencia);
                    break;
                case '09':
                case '10':
                    if ($ocorrencia->codigo != '10') {
                        $actions = [
                            'action' => 'payed',
                            'data' => [
                                'number' => $documento->TituloNossoNumero
                            ]
                        ];
                    } else {
                        $actions = [
                            'action' => 'default',
                            'data' => []
                        ];
                    }
                    break;
                case '12':
                    $actions = [
                        'action' => 'abatementCompleted',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '13':
                    $actions = [
                        'action' => 'abatementCanceled',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'discount_amount' => formatStringToFloat($documento->PagamentoValorAbatimento)
                        ]
                    ];
                    break;
                case '14':
                    $actions = [
                        'action' => 'changeDueDate',
                        'data' => [
                            'number' => $documento->TituloNossoNumero,
                            'data_vencimento' => explode(" ", $documento['TituloDataVencimento'])[0]
                        ]
                    ];
                    break;
                default:
                    $actions = $this->default($documento, $ocorrencia);
                    break;
            }
        }

        return $actions;
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $body,
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access private
     * @return array
     */
    private function execute(string $path, array $opts = [], array $params = []) :array
    {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = 'https://plugboleto.com.br/api/v1';
        if (!$this->config['production']) {
            $url = 'https://homologacao.plugboleto.com.br/api/v1';
        }
        $url .= $path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && !empty($param['value'])) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, !$this->config['upload'] ? json_encode($dados) : $dados);
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }
}
