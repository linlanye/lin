# Lin/1.0
[![Latest Stable Version](https://poser.pugx.org/lin/components/v/stable)](https://packagist.org/packages/lin/components)
[![Total Downloads](https://poser.pugx.org/lin/components/downloads)](https://packagist.org/packages/lin/components)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.2-8892BF.svg)](http://www.php.net/)
[![License](https://poser.pugx.org/lin/components/license)](https://packagist.org/packages/lin/components)

## 介绍

**Lin-components**是[Lin](https://www.lin-php.com)框架的组件代码，这套组件可以独立于框架运行，它涵盖了常用的web方法和功能，是一套完备的web开发组件集合。其基于[basement](https://github.com/linlanye/basement)(一套web常见功能的开发规范)，并提供了更为多样的功能。

## 特性

* 自带高性能**kv、queue**型模拟服务器，无需安装`redis`和`memcached`等缓存或队列服务器，也能一键实现无缝替换为专用服务器。
* 极简主义设计。所有的方法无论命名、调用都保持简单一致，对外屏蔽了复杂的设计模式，**只呈现最基本的php语法**，避免二次学习成本。例如入参和出参都仅有php基础变量。
* 零耦合，组件里的每一个小组件都相互独立，之间没有直接性耦合，**皆可以作为一个单独的包使用**。
* 涵盖场景广。对web应用场景做了深度涵盖，组件所提供的功能可以**满足大型开发**需求。
* 高度整合。对很多相似的功能做了统一化，如对**验证码、oauth、login-in、XSRF、单点登录**等安全场景，都只需一个简单的类`Security`和其仅有的几个方法组合实现。
* 学习简单。所有**复杂的概念都被屏蔽**，使用者开发时的逻辑思维只需单向进行，如关联关系模型中，n对n的直接关联或远程关联等复杂概念都被屏蔽，使用者只需考虑单方向的主从关系（定义主到从模型即可，无需定义从到主模型）


## 功能列表

* [basement组件](https://github.com/linlanye/basement)及功能扩展。
* 完整的ORM，包括模型类和查询构建类，对复杂sql语句具有高度处理能力。
* 全面化的数据处理，包括数据映射、数据格式化、数据校验等。
* 完善的响应方式，json、xml、视图页面、http常见响应等。
* 高性能路由，无论哪种风格的路由皆可以轻松构建并快速解析。
* 完备的安全处理，强化保障各个web安全场景。
* 弹性化的session，可一键切换多种session存储方式。
* 灵活的url生成，无论动态或静态url轻松实现。
* 完备简洁的视图引擎，贴近php的原生语法，并可实现页面的全局或局部静态化。
* 扩展了的mvc模式，提供更灵活和更统一的调度方式，并可实现自动流程控制，省略大量`if-else`。
* 更快速的算法库，如对称加密算法有数据量低、瞬时加解密、动态加密、超高安全特性等。

## 安装

```
配置composer.json文件
"require": {
    "lin/components": "^1.0"
}
执行composer update
```
或
```
composer require lin/components 1.0
```

## 使用

使用前需先通过[basement](https://github.com/linlanye/basement)加载配置文件
```
Linker::Config()::set('lin', include 'config/lin-production.php'); //加载组件配置项
Linker::Config()::set('servers', include 'config/lin-servers.php'); //加载服务器配置项
```


## 详细文档

* [github](https://github.com/linlanye/lin-components-docs)
* [官网](https://docs.lin-php.com)

## 捐赠
![捐赠林澜叶](http://img.lin-php.com/donations.png)

## 版权信息
* 作者：林澜叶(linlanye)版权所有。
* 开源协议：[Apache-2.0](LICENSE)




基于php7.2的全组件化框架，可自定义框架流程，自带kv、queue服务器，无需安装memcache和redis