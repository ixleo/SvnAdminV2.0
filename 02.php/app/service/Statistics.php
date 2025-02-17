<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Statistics extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    public function GetLoadInfo()
    {
        /**
         * ----------负载计算开始----------
         */
        $laodavgArray = sys_getloadavg();

        //获取cpu总核数
        $cpuCount = substr_count(file_get_contents('/proc/cpuinfo'), 'model name');

        //一分钟的平均负载 / （cpu总核数 * 2），超过100则为100 不超100为真实值取整
        $percent = round($laodavgArray[0] / ($cpuCount * 2) * 100, 1);
        if ($percent > 100) {
            $percent = 100;
        }

        /**
         * ----------负载计算结束----------
         */
        $data['load'] = [
            'cpuLoad15Min' => $laodavgArray[2],
            'cpuLoad5Min' => $laodavgArray[1],
            'cpuLoad1Min' => $laodavgArray[0],
            'percent' => $percent,
            'color' => funGetColor($percent)['color'],
            'title' => funGetColor($percent)['title']
        ];

        /**
         * ----------cpu计算开始----------
         */
        /**
         * cpu使用率
         * 
         * %Cpu(s):  0.0 us,  3.2 sy,  0.0 ni, 96.8 id,  0.0 wa,  0.0 hi,  0.0 si,  0.0 st
         * 
         * us user CPU time     用户空间占用CPU百分比
         * sy system CPU time   内核空间占用CPU百分比
         * ni nice CPU          用户进程空间内改变过优先级的进程占用CPU百分比 
         * id idle              空闲CPU百分比
         * wa iowait            等待输入输出的CPU时间百分比
         * hi hardware          硬件中断
         * si software          软件中断 
         * st steal             实时
         */
        $topResult = funShellExec('top -b -n 1 | grep Cpu');
        $topResult = $topResult['result'];
        preg_match('/ni,(.*?)id/', $topResult, $matches);
        $id = 100 - (float)trim($matches[1]);

        //cpu型号
        $cpuModelArray = [];
        $cpuModelName = explode("\n", file_get_contents('/proc/cpuinfo'));
        foreach ($cpuModelName as $value) {
            if (strstr($value, 'model name')) {
                $tempArray = explode(':', $value);
                in_array(trim($tempArray[1]), $cpuModelArray) ? '' : array_push($cpuModelArray, trim($tempArray[1]));
            }
        }

        $cpuPhysical = 0;
        $cpuPhysicalCore = 0;
        $cpuProcessor = 0;
        $cpuInfo = explode("\n", file_get_contents('/proc/cpuinfo'));
        $cpuPhysicalArray = [];
        foreach ($cpuInfo as $value) {
            if (strstr($value, 'physical id')) {
                in_array($value, $cpuPhysicalArray) ? '' : array_push($cpuPhysicalArray, $value);
            } else if (strstr($value, 'cpu cores')) {
                $cpuPhysicalCore++;
            } else if (strstr($value, 'processor')) {
                $cpuProcessor++;
            }
        }
        $cpuPhysical = count($cpuPhysicalArray);

        //总物理核心数 = 物理cpu个数 * 每个物理cpu的物理核心数（每个物理cpu的物理核心数都一样吗？）
        $cpuCore = $cpuPhysical * $cpuPhysicalCore;

        /**
         * ----------cpu计算结束----------
         */
        $data['cpu'] = [
            'percent' => round($id, 1),
            'cpu' => $cpuModelArray,
            'cpuPhysical' => $cpuPhysical,
            'cpuPhysicalCore' => $cpuPhysicalCore,
            'cpuCore' => $cpuCore,
            'cpuProcessor' => $cpuProcessor,
            'color' => funGetColor($id)['color']
        ];

        /**
         * ----------内存计算开始----------
         */
        $meminfo = file_get_contents('/proc/meminfo');

        preg_match_all('/^([a-zA-Z()_0-9]+)\s*\:\s*([\d\.]+)\s*([a-zA-z]*)$/m', $meminfo, $meminfos);

        $meminfos = array_combine($meminfos[1], $meminfos[2]);
        $memTotal = (int)$meminfos['MemTotal'];
        $memUsed = $memTotal - (int)$meminfos['MemFree'] - (int)$meminfos['Cached'] - (int)$meminfos['Buffers'];
        $memFree = $memTotal - $memUsed;

        $percent = round($memUsed / $memTotal * 100, 1);

        /**
         * ----------内存计算结束----------
         */
        $data['mem'] = [
            'memTotal' => round($memTotal / 1024),
            'memUsed' => round($memUsed / 1024),
            'memFree' => round($memFree / 1024),
            'percent' => $percent,
            'color' => funGetColor($percent)['color']
        ];

        return message(200, 1, '成功', $data);
    }

    /**
     * 获取硬盘
     * 
     * 获取硬盘数量和每个硬盘的详细信息
     */
    public function GetDiskInfo()
    {
        $rs = funShellExec('df -lh | grep -E "^(/)"');
        $rs = $rs['result'];

        //将多个连续的空格换为一个
        $result = preg_replace("/\s{2,}/", ' ', $rs);

        //多个硬盘
        $diskArray = explode("\n", $result);

        $data = [];

        //处理
        foreach ($diskArray as $value) {
            if (trim($value) != '') {
                $diskInfo = explode(" ", $value);
                array_push($data, [
                    'fileSystem' => $diskInfo[0],
                    'size' => $diskInfo[1],
                    'used' => $diskInfo[2],
                    'avail' => $diskInfo[3],
                    'percent' => (int)str_replace('%', '', $diskInfo[4]),
                    'mountedOn' => $diskInfo[5],
                    'color' => funGetColor((int)str_replace('%', '', $diskInfo[4]))['color']
                ]);
            }
        }

        return message(200, 1, '成功', $data);
    }

    /**
     * 获取统计
     * 
     * 操作系统类型
     * 仓库占用体积
     * SVN仓库数量
     * SVN用户数量
     * SVN分组数量
     * 计划任务数量
     * 运行日志数量
     */
    public function GetStatisticsInfo()
    {
        //操作系统类型和版本
        if (file_exists('/etc/redhat-release')) {
            $os = file_get_contents('/etc/redhat-release');
        } else if (file_exists('/etc/lsb-release')) {
            $os = file_get_contents('/etc/lsb-release');
        } else {
            $os = 'Linux';
        }

        $aliaseCount = $this->SVNAdmin->GetAliaseInfo($this->authzContent);
        if (is_numeric($aliaseCount)) {
            $aliaseCount = -1;
        } else {
            $aliaseCount = count($aliaseCount);
        }

        $backupCount = 0;
        $files = scandir($this->configSvn['backup_base_path']);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (!is_dir($this->configSvn['backup_base_path'] . '/' . $file)) {
                    $backupCount++;
                }
            }
        }

        return message(200, 1, '成功', [
            'os' => trim($os),

            'repCount' => $this->database->count('svn_reps'),
            'repSize' => funFormatSize($this->database->sum('svn_reps', 'rep_size')),

            'backupCount' => $backupCount,
            'backupSize' => funFormatSize(funGetDirSizeDu($this->configSvn['backup_base_path'])),

            'logCount' => $this->database->count('logs', ['log_id[>]' => 0]),

            'adminCount' => $this->database->count('admin_users'),
            'subadminCount' => $this->database->count('subadmin'),
            'userCount' => $this->database->count('svn_users'),
            'groupCount' => $this->database->count('svn_groups'),
            'aliaseCount' => $aliaseCount,
        ]);
    }
}
