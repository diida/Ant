<?php
/**
 * antc             ��Դ���࣬����ģ�����ͼ����
 * @name            antc
 * @author          diida
 * @version         4.0
 */
class antc
{
    /**
     * ��ִ�з���ֵ
     */
    public $selfExecuteResult = false;
    /**
     * �Ƿ�ӵ����ͼ��һЩAJAX������߽ӿڣ�����û����ͼ
     * @var bool
     */
    protected $hasView = true;
    /**
     * ������Ҫչʾ������
     * @var array
     */
    public $displayParam = array();
    /**
     * ģ��� rs ����ָ��ģ��λ�ã���ͬ�Ŀ���������ʹ����ͬ��ģ��
     * @var null
     */
    public $tprs = null;
    public $tpact = null;
    /**
     * ָ��request��λ��
     * @var array|null
     */
    public $requestAct = null;
    /**
     * @var antp
     */
    public $tp;
    /**
     * @var antr
     */
    public $request;
    /**
     * ÿ������Ŀ¼�е��ļ�����
     * @var int
     */
    public $cachePage = 50;
    /**
     * ����ʱ�䳤��
     * @var int
     */
    public $cache = 0;
    /**
     * �����ļ���
     * @var null
     */
    public $cacheFileName = '';
    /**
     * �Ƿ�����д����
     * @var bool
     */
    public $writeAble = true;

    public $rs;
    public $act;

    /**
     * ���ھ�̬����run��ʵ�ֹ��ڸ��ӣ������д���Ķ��ܴ��������ղ����������ɶ���ķ�ʽ��ʵ���������
     * ͨ��:
     * new rs_index_help(true);����ִ��rs=index&act=help�������������
     * @param bool $selfExecute
     * @param array $displayParam
     */
    function __construct($selfExecute = false,$displayParam = array())
    {
        if($selfExecute) {
            $name = get_class($this);
            $namePieces = explode('_',$name);
            $this->init(strtolower($namePieces[1]),strtolower($namePieces[2]));

            $r = ant::getRequest($this);
            $this->displayParam = $displayParam;
            $type = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            if($type == 'GET')
                $this->selfExecuteResult = $this->exec($r);
            else
                $this->selfExecuteResult = $this->post($r);
            $this->display();
        }
    }

    function init($rs,$act)
    {
        $this->rs = $rs;
        $this->act = $act;
        if ($this->requestAct === null)
            $this->requestAct = array($this->rs, $this->act);
    }

    function noView()
    {
        $this->hasView = false;
    }

    function useView()
    {
        $this->hasView = true;
    }

    function exec(antr $r)
    {

    }

    function post(antr $r)
    {

    }

    function assign($name, $value)
    {
        $this->displayParam[$name] = $value;
    }

    function getAssign($name)
    {
        if (isset($this->displayParam[$name]))
            return $this->displayParam[$name];
        else
            return null;
    }

    function display()
    {
        $this->tp = new antp($this->rs, $this->act);

        if ($this->hasView == false) return $this->tp;
        if ($this->tpact) $this->tp->act = $this->tpact;
        if ($this->tprs) $this->tp->rs = $this->tprs;
        $this->displayParam['r'] = $this->request;
        $this->tp->loadData($this->displayParam);

        if ($this->cache > 0 && $this->writeAble) {
            $s = $this->tp->sdisplay();
            $fp = fopen($this->cacheFileName, 'w');
            fwrite($fp, $s);
            fclose($fp);
            echo $s;
        } else {
            $this->tp->display();
        }

        return $this->tp;
    }

    function useCache(antr $r)
    {
        if ($this->cache <= 0) return false;

        $id = $r->getValue('cache_id');
        $cache_id = get_class($this);

        if ($id !== null) {
            if (is_numeric($id)) {
                $d = ceil($id / $this->cachePage);
                $dir = PATH_CACHE . $cache_id . DS . $d . DS;
            } else {
                $dir = PATH_CACHE . $cache_id . DS;
            }

            $fn = $dir . $id . '.html';
        } else {
            $dir = PATH_CACHE;
            $fn = $dir . $cache_id . '.html';
        }

        $this->cacheFileName = $fn;

        if (file_exists($dir) == false) {
            if (!mkdir($dir, 0755, true)) {
                $this->cache = 0;
            }
        }

        if (file_exists($fn)) {
            $fp = fopen($fn, 'r');
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if ($r->get('ant_clear_cache')->value() == 1) {
                    //��̨�����建��ķ���
                } else {
                    $time = time() - filemtime($fn);
                    if (($time / 60) <= $this->cache) {
                        echo file_get_contents($fn);
                        return true;
                    }
                }
                fclose($fp);
                @unlink($fn);
                $this->writeAble = true; //��Ȩд
            } else {
                $this->writeAble = false; //����Ȩд
                echo file_get_contents($fn); //ֻ�ܶ�
                return true;
            }
        }

        return false;
    }

    function forceTp($act, $rs = null)
    {
        $this->tpact = $act;
        if ($rs) $this->tprs = $rs;
    }

    /**
     * ���ô˺�����ȷ��JSON�����ʽ�̶�
     * @param  $success
     * @param string $data
     * @param null $message
     * @return void
     */
    function jsonResult($success, $data = '', $message = null)
    {
        $this->noView();
        $message = iconv('gbk', 'utf-8', $message);
        $c = new stdClass();
        /** @noinspection PhpUndefinedFieldInspection */
        $c->success = $success;
        $c->data = $data;
        if ($message !== null) {
            /** @noinspection PhpUndefinedFieldInspection */
            $c->message = $message;
        }
        $s = json_encode($c);
        if (isset($_GET['callback'])) {
            $s = addslashes($s);
            echo $_GET['callback'] . "(\"$s\")";
        } else
            echo $s;
    }

    function jsonError()
    {
        $e = ant::getErrorInfo();
        if ($e) {
            $this->jsonResult(false, $e['errno'], $e['error']);
        } else {
            $this->jsonResult(true, '', 'no error');
        }
    }

    /**
     * ��ȡIP
     * @static
     * @return string
     */
    static function returnIp()
    {
        $ip = "-1";
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_a = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            for ($i = 0; $i < count($ip_a); $i++) { //
                $tmp = trim($ip_a[$i]);
                if ($tmp == 'unknown' || $tmp == '127.0.0.1' || strncmp($tmp, '10.', 3) == 0 || strncmp($tmp, '172', 3) == 0 || strncmp($tmp, '192', 3) == 0)
                    continue;
                $ip = $tmp;
                break;
            }
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = trim($_SERVER['HTTP_CLIENT_IP']);
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }

    /**
     * ���ظ�ʽ����ı�׼url
     * �ܶ�ʱ�����ǻ���url�в�дindex����������Ჹ��
     * @static
     * @return string
     */
    static function returnUrl()
    {
        $uri = '';
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $rs = isset($_GET['rs']) ? $_GET['rs'] : 'index';
            $act = isset($_GET['act']) ? $_GET['act'] : 'index';

            ksort($_GET);
            $uri .= '/index.php?rs=' . $rs . '&act=' . $act;
            if (!empty($_GET)) {
                foreach ($_GET as $k => $v) {
                    if (in_array($k, array('rs', 'act'))) continue;
                    $uri .= "&$k=$v";
                }
            }
        }
        return $uri;
    }

    /**
     * ant֧����������һ��������
     * ant::action('index','help');//���ð���ҳ��,�������ҳ��Ŀ������������޷���λ��������(IDE,��Ŀ�������)
     * �����ṩһ�ָ��������Ķ��Ĵ�����д��ʽ
     * rs_index_help::run();//������rs/index/help.php
     * ���ַ������кô��������ڿ�������������ı������£��ڶ��ֽ������Ѻ�
     * ��������븴��������뵽ÿ���������У�����__CLASS__�޷�����ʹ�ã�ϣ��֮��PHP�ܹ��ṩ���õ�֧��
     *
     * @static
     * @param array $displayParam
     * @param string $type
     * @return bool
     */
    static function run($displayParam = array(),$type = 'GET')
    {
        $name = __CLASS__;
        $namePieces = explode('_',$name);
        return ant::action($namePieces[1],$namePieces[2],$displayParam,$type);
    }
}
