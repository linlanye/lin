<!--
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-07 08:57:42
 * @Modified time:      2018-09-09 23:08:56
 * @Depends on Linker:  None
 * @Description:        测试模板标签输出，变量输出和函数输出，语法标签解析
 -->

{use lin\tests\components\ViewTest as SomeClass;}

<div output="{:$value_var}">
	{:value_var}<!-- 两种输出变量类型，后一种是单变量输出 -->
</div>

<div output="{:SomeClass::outputMD5($value_var)}">
	{:SomeClass::outputMD5($value_var)}<!-- 输出函数方法 -->
</div>

<div output="{:value_if}">
	{if ($if) }
		{:value_if}
	{else}
		{:'something'}
	{/if}
</div>

<div output="{:value_switch}">
	{switch ($switch)}
		{case 1}
			{:value_switch}
			{break}
		{case 'something'}
			{:'something'}
	{/switch}
</div>

<div output="{:value_while}">
	{while($while)}
		{:value_while}
		{ break;}
	{/while}
</div>

{foreach ($value_array as $k=>$v)}
	<div output="{:v}">
		{:v}
	</div>
{/foreach}

<div output="{:escape}">
	\{:escape} <!--转义标签 -->
</div>
