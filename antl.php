<?php
/**
 * ant loader   ������ؿ���ļ�
 * �����ļ�ʹ��autoLoad����
 * @name        antl
 * @author      ���ӿ� (diida)
 * @version     4.0
 */
class antl
{
    /**
     * @var antl
     */
    static $ins = NULL;
    public $display_data;

    /**
     * @static
     * @return antl|null
     */
    static function getInstance()
    {
        if (self::$ins == NULL) self::$ins = new self();
        return self::$ins;
    }

    /**
     * load
     * �������ɿ�������ģ�飬��ͼ�������ַ������,Ҳ�涨�˿�ܺ���Ŀ��Ŀ¼�ṹ
     * @name        load
     * @param    $__type �ַ���,ȡֵΪ act,tpl,cache,request,module
     * @param    $rs ��Դ
     * @param    $act ������
     * @access    public
     * @return     ����·����ʧ�ܷ���false
     * antl::getInstance()->load('file','file/gameconfig/212_interface.php');
     */
    public function load($__type, $rs, $act = '', $__data = array())
    {
        $fn = $this->pathFix($__type, $rs, $act);
        if (file_exists($fn)) {
            if ($__type == 'tpl') {
                if ($__data) extract($__data);
                $this->display_data = $__data;
                $displayParam = $__data;
            }
            include_once($fn);
            return $fn;
        }
        return false;
    }

    public function pathFix($__type, $rs, $act = '')
    {
        switch ($__type) {
            case 'act':
                return PATH_RS . $rs . DS . $act . '.php';
            case 'tpl':
                if ($rs === null)
                    return PATH_TPL . $act . '.php';
                else
                    return PATH_TPL . $rs . DS . $act . '.php';
            case 'request':
                return PATH_REQUEST . $rs . DS . $act . '.php';
            case 'file':
                return PATH_ROOT . $rs;
            default:
                return false;
        }
    }

    function loadTpl($rs, $act, $data = null)
    {
        return $this->load('tpl', $rs, $act, $data);
    }

    /**
     * autoLoad
     * @static
     * @param  $c
     * @return void
     */
    static function autoload($c)
    {
        $c = strtolower($c);
        $path = str_replace('_', '/', $c);
        if (file_exists(PATH_ROOT . $path . '.php'))
            require_once(PATH_ROOT . $path . '.php');
    }

    static function useAutoload()
    {
        spl_autoload_register(array('antl', 'autoload'));
    }
}
