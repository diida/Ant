<?php
/**
 * antr Ant �ı�׼���,���ڱ�д�������
 * �����Ψһ��ƫ�����Ե�ģ�飬Ϊ����ǿ��ʹ�ñ�׼�Ĺ��˷�ʽ
 * ������д�������ִ���
 * $keyWord = isset($_GET['keyword']) ? $_GET['keyword'] : '';
 * $keyWord = $_GET['keyword'] ? $_GET['keyword'] : '';//Notice error
 * $keyWord = $r->get('keyword')->xss()->trim()->setDefault('')->value();
 * OR
 * $keyWord = $r->get('keyword')
 *              ->xss()
 *              ->trim()
 *              ->setDefault('')
 *              ->value();//Easy to read
 * @name             antr
 * @author           ���ӿ� (Kevin)
 * @copyright        Ant
 * @E-mail           kevinaccess@live.cn
 * @example          $r->get('cid')->int()->xss()->value();
 * @version          4.0
 */
class antr
{
    /**
     * @var antc $act
     */
    public $act = null;
    protected $_val = null;
    protected $_key = null;
    protected $_data = array();
    protected $_type = 'get';
    protected $_tpl = '';
    protected $_name = '';
    protected $error = array();
    protected $warning = array();
    public $findAllErrors = true;
    public $failed = false;

    public function passed()
    {
        return !$this->failed;
    }

    public function exec()
    {

    }

    public function deal($type, &$array = array(), $key = null, $name = '')
    {
        if ($this->getData($type, $key)) {
            return $this->keep($type, $key);
        }

        $this->_type = $type;
        if ($key !== null) {
            $this->_key = $key;
            if (isset($array[$key]))
                $this->_val = $array[$key];
            else
                $this->_val = null;
            if (empty($name)) {
                $this->_name = $key;
            } else {
                $this->_name = $name;
            }
        }
        $this->save();
        return $this;
    }

    public function save()
    {
        if (!isset($this->_data[$this->_type])) {
            $this->_data[$this->_type] = array();
        }

        $this->_data[$this->_type][$this->_key] = array($this->_val, $this->_name);
        return $this;
    }

    public function get($key = null, $name = '')
    {
        return $this->deal('get', $_GET, $key, $name);
    }

    public function post($key = null, $name = '')
    {
        return $this->deal('post', $_POST, $key, $name);
    }

    public function cookie($key = null, $name = '')
    {
        return $this->deal('cookie', $_COOKIE, $key, $name);
    }

    //==== ���������� ============================================================================ 
    /**
     * �ж�һ�������Ƿ���int���ͣ��������ַ���int
     * @return antr
     */
    function int($flag = true, $error = null, $errno = null)
    {
        if (!is_int($this->_val) && preg_match('/^\d+$/', $this->_val) === 0) {
            $this->setError($flag, $error, $errno, 'int');
        }
        $this->save();
        return $this;
    }

    /**
     * ��֤�����룬�������ַ����Ⱥ�δ���õ��������
     * @return antr
     */
    function isEmpty($flag = true, $error = null, $errno = null)
    {
        if ($this->_val === null || $this->_val === '') {
            $this->setError($flag, $error, $errno, 'isEmpty');
        }
        $this->save();
        return $this;
    }

    /**
     * ��֤��������
     * @return antr
     */
    function number($flag = true, $error = null, $errno = null)
    {
        if (strlen($this->_val) > 0)
            if (!is_numeric($this->_val)) {
                $this->setError($flag, $error, $errno, 'number');
            }
        $this->save();
        return $this;
    }

    /**
     * ��֤�ַ�������,ascii
     * @return antr
     *
     */
    function length($flag, $max, $min = 1, $error = null, $errno = null)
    {
        $l = strlen($this->_val);
        if ($l < $min || $l > $max) {
            $this->setError($flag, $error, $errno, 'length');
        }
        $this->save();
        return $this;
    }

    /**
     * ��֤���
     * @return antr
     */
    function equal($val, $flag = true, $error = null, $errno = null)
    {
        if ($this->_val !== $val) {
            $this->setError($flag, $error, $errno, 'equal');
        }
        $this->save();
        return $this;
    }

    /**
     * ����
     * @return antr
     */
    function xss()
    {
        $this->_val = $this->filterXSS($this->_val);
        $this->save();
        return $this;
    }

    //==== ���������� END============================================================================
    /**
     * ���ô�����Ϣ, �����û��ṩ�Ĵ���ģ��
     * @throws Exception
     * @param  $flag
     * @param  $error
     * @param  $errno
     * @param null $systpl
     * @return void
     */
    function setError($flag, $error, $errno, $systpl = null)
    {
        $this->_val = null;
        if (empty($error)) {
            $tpl = $GLOBALS['ant']['antr_error'][$systpl];
        } else {
            $tpl = $error;
        }
        $name = $this->_name;
        $value = $this->_val;
        $error = eval('return "' . $tpl . '";');
        $e = array(
            'error' => $error, 'errno' => $errno
        );

        if ($flag == false) {
            if (!isset($this->warning[$this->_type])) {
                $this->warning[$this->_type] = new ante();
            }
            $this->warning[$this->_type]->setError($error, $errno, $this->_key);
        } else {
            $this->failed = true;
            if (!isset($this->error[$this->_type])) {
                $this->error[$this->_type] = new ante();
            }
            //print_r(get_class_methods($this->error[$this->_type]));die;
            $this->error[$this->_type]->setError($error, $errno, $this->_key);
        }
        if ($flag == true && $this->findAllErrors == false) {
            throw new Exception($error, $errno);
        }
    }

    /**
     * @param null $type
     * @return ante
     */
    function getErrors($type = null)
    {
        if ($type == null)
            return $this->error;
        else
            return $this->error[$type];
    }

    function getWarnings($type = null)
    {
        if ($type == null)
            return $this->warning;
        else
            return $this->warning[$type];
    }

    function getErrorString()
    {
        if (isset($this->error[$this->_type])) {
            /**
             * @var ante $e
             */
            $e = $this->error[$this->_type];
            return $e->formatErrorStack('html', false);
        }
        return '';
    }

    function value()
    {
        return $this->getData($this->_type, $this->_key);
    }

    function setDefault($def)
    {
        $v = $this->getData($this->_type, $this->_key);
        if ($v === null) {
            $this->_val = $def;
        }
        $this->save();
        return $this;
    }

    /**
     * ����һ��ֵ������������
     */
    function setValue($value)
    {
        $this->_val = $value;
        return $this;
    }

    function filterXSS($str)
    {
        return preg_replace('/[\:\<\>\!\[\]\{\}\(\)\;\\\]/i', '', $str);
    }

    /**
     * ʹ��ϵͳ�Դ��ĺ�����һЩ����
     * @return antr
     */
    function __call($f, $args)
    {
        if (function_exists($f) && $this->_val !== null) {
            if (is_array($args)) array_unshift($args, $this->_val);
            else
                $args = array($this->_val);
            $this->_val = call_user_func_array($f, $args);
        }
        return $this;
    }

    function run()
    {
        try {
            $this->exec();
        } catch (Exception $e) {
            if (defined('DEBUG')) {
                echo $this->getErrorString();
                die;
            }
            return false;
        }

        return !$this->failed;
    }

    function getValue($key)
    {
        return $this->getData('get', $key);
    }

    function postValue($key)
    {
        return $this->getData('post', $key);
    }

    function cookieValue($key)
    {
        return $this->getData('cookie', $key);
    }

    protected function getData($type, $key)
    {
        if (!isset($this->_data[$type][$key]))
            return null;
        return $this->_data[$type][$key][0];
    }

    protected function getName($type, $key)
    {
        if (!isset($this->_data[$type][$key]))
            return null;
        return $this->_data[$type][$key][1];
    }

    public function trim()
    {
        $this->_val = trim($this->_val);
        $this->save();
        return $this;
    }

    /**
     * @param  $type
     * @param  $key
     * @return antr
     */
    function keep($type, $key)
    {
        $this->_type = $type;
        $this->_key = $key;
        $this->_val = $this->getData($type, $key);
        $this->_name = $this->getName($type, $key);
        return $this;
    }

    static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest') {
            return true;
        }
        return false;
    }

}