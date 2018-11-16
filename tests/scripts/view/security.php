<!--
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-07 08:57:42
 * @Modified time:      2018-09-09 23:14:32
 * @Depends on Linker:  None
 * @Description:        测试分配变量安全
 -->
{STATIC}
{foreach ($array as $v)}
	{foreach ($v as $v2)}
		{:v2}<!-- 多重变量也被安全处理 -->
	{/foreach}
{/foreach}

{:var}

{/STATIC}
