# Lin 1.0
[![Latest Stable Version](https://poser.pugx.org/lin/lin/v/stable)](https://packagist.org/packages/lin/lin)
[![Total Downloads](https://poser.pugx.org/lin/lin/downloads)](https://packagist.org/packages/lin/lin)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.2-8892BF.svg)](http://www.php.net/)
[![License](https://poser.pugx.org/lin/lin/license)](https://packagist.org/packages/lin/lin)

## 介绍

**Lin是**一套基于php7.2的全新web框架，它具有一套全新的开发理念，避免了以往web框架的缺点，完美实现了三重分离：应用层、框架层、组件层。使用者只需通过堆积木形式将一个个功能进行组装即可，而无需花费大量精力去学习一个框架。Lin解耦了绝大多数开发场景，让协同开发更为简单，并且应用结构从一开始就基于高度弹性化的架构模式，对后续扩展、维护、升级都可以0成本轻松实现。

## 特性

* 全组件化，框架运行流程完全由使用者自行控制，通过一个个组件堆积而成。
* 自带模拟kv、queue服务器，无需安装memcache和redis等外部环境，并可轻松一键切换。
* 新的组织架构，解决传统MVC模式的短板，可对应用轻量化弹性升级，该架构称为LBA(Layer, Block, Affix，见下述解释)。
* 涵盖web开发的绝大多数场景，组件功能接口简单，学习接近0成本。（参见**[lin/components](http://github.com/linlanye/lin-components)**）
* 生成环境和开发环境无缝替换，生产部署极致简单。


## LBA架构

LBA（Layer, Block, Affix）架构由层、块、摆件三个部分构成，由**林澜叶**独自提出。

* **层**：核心架构所在，由整套不同的逻辑单元组成，彼此之间相互独立，是整个应用的骨架部分。如缓存层、数据访问层、控制器层、响应层等等。不同的层提供不同的应用场景，一个层可以看作一个用于调度不同功能的类。
* **块**：依托于层而存在，为层提供一种功能，是对层功能的强化，是整个应用的血肉部分。一个块可以看作是具有某个功能类，不同的块在同一个层中构成一个完备的结构。如在数据访问层中，数据模型提供对数据库的对象化操作，数据格式化器和映射器则提供存储数据到应用数据的一个映射。
* **摆件**：作为对层的点缀或装饰，是一种可选的功能，它的添加和移除对整个应用架构没有影响。不同的摆件可以看作是一种功能扩展，它可以是一个类，也可以是一个脚本，一句代码，起到强化应用的作用。如视图模版、路由文件、语言包等都可以看作摆件。应用可以无需视图（API开发），也可以无需路由(仅通过层来调度)，更可以无需语言包。

MVC的Model对应块，View对应LBA的摆件，Controller则对应层。基于MVC的各种变体也能在LBA架构中找到对应，实际上LBA正是对这套架构体系的一个更抽象的扩展，它能够适用于更大型的应用架构中。

## 目录结构

初始主要结构如下：
~~~
your_app
│
├─app                               应用目录
│  ├─affix                          摆件目录
│  │  ├─event                       事件目录
│  │  ├─lang      	                语言包目录
│  │  ├─response                    响应目录
│  │  │  ├─jsonxml                  json和xml的模版目录
│  │  │  └─view                     视图模版目录
│  │  │
│  │  └─route                       路由规则目录
│  │
│  ├─block                          块目录
│  │  ├─formatter                   数据格式化器目录
│  │  ├─mapper                      数据映射器目录
│  │  ├─model                       数据模型目录
│  │  └─validator                   数据验证器目录
│  │
│  ├─config                         配置目录
│  │  ├─lin-servers.php  		    服务器配置
│  │  ├─lin-servers.production.php  境服务器配置（生成环境）
│  │  ├─lin.php                     组件配置
│  │  ├─lin.production.php          组件配置（生成环境）
│  │
│  ├─layer                          层目录
│  │  ├─Error.php                   错误层
│  │  ├─Index.php                   初始控制器层
│  │  └─Test.php                    组件使用的测试文件（无实际用途）
│  │
│  ├─lib                            库目录
│  │  └─helper.php                  lin组件的助手函数
│  │
│  ├─boot.production.php            启动文件（生成环境）
│  ├─boot.php                       启动文件
│  └─register.php                   basement组件注册文件
│
├─public                            入口根目录
│  ├─resource                       资源文件
│  └─index.php                      入口文件
│
├─vendor                            组件目录
│  ├─composer                       composer组件
│  ├─basement                       basement组件
│  └─lin                            lin组件
│
~~~


## 安装

```
//1.composer方式
composer create-project lin/lin lin

//2.源码+composer
进入源码根目录，执行composer install

//3.下载压缩文件
https://downloads.php-lin.com/v1_0_0.zip
```


## 使用

* 在`app/boot.php`文件中定义整个应用执行流程。同理编写生产环境的`app/boot.production.php`文件
* 在`app/register.php`文件中注册[basement](http://github.com/linlanye/basement)的标准组件。
* 在`app/layer`目录中，根据应用复杂度，建立不同的层类或建立不同的目录归档不同的层。
* 在`app/block`目录中，根据具体业务场景，编写各种数据模型、映射、校验、格式化等。
* 在`app/affix`目录中，根据需求定义事件、路由、多语言、视图页面或json的响应模版。
* 在`app/config`目录中，根据实际情况更改配置文件。
* 在`app/lib`目录中，存放自己的库函数或第三方类库。
* 生成环境下，更改`public/index.php`中启动文件为`app/boot.production.php`。


## 开发建议
使用basement组件的情况，尽量使用`Linker`类来调用，具体见[basement](http://github.com/linlanye/basement)。


## 详细文档
* [github](https://github.com/linlanye/lin-components-docs)
* [官网](https://docs.lin-php.com)

## 捐赠
![捐赠林澜叶](http://img.lin-php.com/donations.png)

## 版权信息
* 作者：林澜叶(linlanye)版权所有。
* 开源协议：[Apache-2.0](LICENSE)
