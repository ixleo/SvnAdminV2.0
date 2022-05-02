<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-02 23:08:48
 * @Description: QQ:1801168257
 */

/**
 * 如果要修改
 * 需要在安装软件之前修改该值 安装过程中会自动读取并操作
 */
define('CONFIG_PATH', '/home/svnadmin/');

/**
 * SVN仓库父目录
 */
define('SVN_REPOSITORY_PATH', CONFIG_PATH . 'rep/');

/**
 * svnserve环境变量文件
 */
define('SVNSERVE_ENV_FILE', CONFIG_PATH . 'svnserve');

/**
 * SVN仓库权限配置文件
 */
define('SVN_CONF_FILE', CONFIG_PATH . 'svnserve.conf');

/**
 * authz文件
 */
define('SVN_AUTHZ_FILE', CONFIG_PATH . 'authz');

/**
 * passwd文件
 */
define('SVN_PASSWD_FILE', CONFIG_PATH . 'passwd');

/**
 * 备份文件夹
 */
define('SVN_BACHUP_PATH', CONFIG_PATH . 'backup/');

/**
 * 日志文件夹
 */
define('SVNADMIN_LOG_PATH', CONFIG_PATH . 'logs/');

/**
 * svnserve运行日志
 */
define('SVNSERVE_LOG_FILE', SVNADMIN_LOG_PATH . 'svnserve.log');

/**
 * 临时数据目录
 */
define('TEMP_PATH', CONFIG_PATH . 'temp/');

/**
 * 模板文件目录
 */
define('TEMPLETE_PATH', CONFIG_PATH . 'templete/');

/**
 * 初始化仓库结构模板
 */
define('TEMPLETE_INIT_STRUTCT', TEMPLETE_PATH . '01/');
