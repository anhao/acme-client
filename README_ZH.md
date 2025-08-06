# ACME 客户端

一个全面的 PHP ACME v2 客户端库，用于自动化 SSL/TLS 证书管理，支持 Let's Encrypt、ZeroSSL 和其他兼容 ACME 的证书颁发机构。

[![github stats](https://img.shields.io/github/stars/anhao/acme-client?style=flat-square&label=github%20stats)](https://github.com/anhao/acme-client)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/anhao/acme-client)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-blue.svg)](https://www.php.net/)

> **Language / 语言**: [English](README.md) | [中文](README_ZH.md)


## 功能特性

- **ACME v2 协议支持**：完全兼容 ACME v2 规范
- **多 CA 支持**：支持 Let's Encrypt、ZeroSSL 和其他 ACME 提供商
- **账户管理**：创建、存储和管理 ACME 账户
- **证书操作**：申请、续签和撤销 SSL 证书
- **域名验证**：支持 HTTP-01 和 DNS-01 挑战
- **ARI 支持**：自动续签信息，优化续签时机
- **灵活的密钥类型**：支持 RSA 和 ECC 密钥
- **全面日志记录**：内置 PSR-3 兼容日志记录
- **易于集成**：简单直观的 API 设计

## 系统要求

- PHP 8.2 或更高版本
- OpenSSL 扩展
- cURL 扩展
- JSON 扩展
- mbstring 扩展

## 安装

通过 Composer 安装：

```bash
composer require alapi/acme-client
```

## 快速开始

### 1. 创建本地账户密钥

您有两种方式来创建和管理 ACME 账户密钥：

**方案 A：使用现有密钥和 Account 类**

```php
<?php
require_once 'vendor/autoload.php';

use ALAPI\Acme\Accounts\Account;

// 从现有私钥字符串创建账户
$privateKeyPem = '-----BEGIN PRIVATE KEY-----...-----END PRIVATE KEY-----';
$account = new Account($privateKeyPem);

// 或者使用私钥和公钥创建账户
$publicKeyPem = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';
$account = new Account($privateKeyPem, $publicKeyPem);

// 或者仅使用私钥创建账户（公钥将自动提取）
$account = Account::fromPrivateKey($privateKeyPem);
```

**方案 B：使用 AccountStorage 进行基于文件的密钥管理**

```php
<?php
require_once 'vendor/autoload.php';

use ALAPI\Acme\Utils\AccountStorage;

// 创建新的 ECC 账户并保存到文件（推荐）
$account = AccountStorage::createAndSave(
    directory: 'storage',
    name: 'my-account',
    keyType: 'ECC',
    keySize: 'P-384'
);

// 或者创建 RSA 账户并保存到文件
$rsaAccount = AccountStorage::createAndSave(
    directory: 'storage', 
    name: 'my-rsa-account',
    keyType: 'RSA',
    keySize: 4096
);

echo "账户密钥创建并保存成功！\n";
```

### 2. 初始化 ACME 客户端

```php
<?php
require_once 'vendor/autoload.php';

use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Accounts\Account;
use ALAPI\Acme\Utils\AccountStorage;
use ALAPI\Acme\Http\Clients\ClientFactory;

// 方案 A：从文件加载账户
$account = AccountStorage::loadFromFiles('storage', 'my-account');

// 方案 B：从现有密钥创建账户
$privateKey = '-----BEGIN PRIVATE KEY-----...-----END PRIVATE KEY-----';
$account = new Account($privateKey);

// 创建 HTTP 客户端（可选代理配置）
$httpClient = ClientFactory::create(timeout: 30, options: [
    // 'proxy' => 'http://proxy.example.com:8080'
]);

// 初始化 Let's Encrypt 生产环境客户端
$acmeClient = new AcmeClient(
    staging: false, // 测试时设置为 true
    localAccount: $account,
    httpClient: $httpClient
);

// 或者使用 ZeroSSL
$zeroSslClient = new AcmeClient(
    localAccount: $account,
    httpClient: $httpClient,
    baseUrl: 'https://acme.zerossl.com/v2/DV90/directory'
);
```

### 3. 注册 ACME 账户

Let's Encrypt（无需 EAB）：

```php
try {
    // 在 Let's Encrypt 注册账户
    $accountData = $acmeClient->account()->create(
        contacts: ['mailto:admin@example.com']
    );
    
    echo "账户注册成功！\n";
    echo "账户 URL: " . $accountData->url . "\n";
} catch (Exception $e) {
    echo "注册失败: " . $e->getMessage() . "\n";
}
```

ZeroSSL（需要 EAB）：

```php
try {
    // 从 ZeroSSL 控制台获取 EAB 凭据
    $eabKid = 'your-eab-kid';
    $eabHmacKey = 'your-eab-hmac-key';
    
    $accountData = $zeroSslClient->account()->create(
        eabKid: $eabKid,
        eabHmacKey: $eabHmacKey,
        contacts: ['mailto:admin@example.com']
    );
    
    echo "ZeroSSL 账户注册成功！\n";
} catch (Exception $e) {
    echo "注册失败: " . $e->getMessage() . "\n";
}
```

### 4. 申请证书

```php
<?php
use ALAPI\Acme\Enums\AuthorizationChallengeEnum;

try {
    // 获取账户数据
    $accountData = $acmeClient->account()->get();
    
    // 为域名创建新订单
    $domains = ['example.com', 'www.example.com'];
    $order = $acmeClient->order()->new($accountData, $domains);
    
    echo "订单已创建: " . $order->url . "\n";
    echo "状态: " . $order->status . "\n";
    
    // 检查域名验证
    $validations = $acmeClient->domainValidation()->status($order);
    
    foreach ($validations as $validation) {
        $domain = $validation->identifier['value'];
        echo "域名: $domain - 状态: " . $validation->status . "\n";
        
        if ($validation->isPending()) {
            // 获取 HTTP-01 挑战的验证数据
            $challenges = $acmeClient->domainValidation()->getValidationData(
                [$validation], 
                AuthorizationChallengeEnum::HTTP
            );
            
            foreach ($challenges as $challenge) {
                echo "域名 $domain 的 HTTP 挑战:\n";
                echo "  文件名: " . $challenge['filename'] . "\n";
                echo "  文件内容: " . $challenge['content'] . "\n";
                echo "  放置路径: http://$domain/.well-known/acme-challenge/" . $challenge['filename'] . "\n\n";
            }
        }
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
```

### 5. 完成域名验证

将挑战文件放置到 Web 服务器后：

```php
try {
    // 为每个域名触发验证
    foreach ($validations as $validation) {
        if ($validation->isPending()) {
            $response = $acmeClient->domainValidation()->validate(
                $accountData,
                $validation,
                AuthorizationChallengeEnum::HTTP,
                localTest: true // 先执行本地验证
            );
            
            echo "已触发域名验证: " . $validation->identifier['value'] . "\n";
        }
    }
    
    // 等待验证完成
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        sleep(5);
        $attempt++;
        
        // 检查订单状态
        $currentOrder = $acmeClient->order()->get($accountData, $order->url);
        echo "订单状态: " . $currentOrder->status . "\n";
        
        if ($currentOrder->status === 'ready') {
            echo "所有验证已成功完成！\n";
            break;
        }
        
        if ($currentOrder->status === 'invalid') {
            echo "订单验证失败！\n";
            break;
        }
        
    } while ($attempt < $maxAttempts);
    
} catch (Exception $e) {
    echo "验证错误: " . $e->getMessage() . "\n";
}
```

### 6. 生成并提交 CSR

```php
use ALAPI\Acme\Security\Cryptography\OpenSsl;

try {
    if ($currentOrder->status === 'ready') {
        // 生成证书私钥
        $certificatePrivateKey = OpenSsl::generatePrivateKey('RSA', 2048);
        
        // 使用 OpenSsl 助手生成证书签名请求（CSR）
        $csrString = OpenSsl::generateCsr($domains, $certificatePrivateKey);
        
        // 导出私钥用于保存
        $privateKeyString = OpenSsl::openSslKeyToString($certificatePrivateKey);
        
        // 提交 CSR 完成订单
        $finalizedOrder = $acmeClient->order()->finalize(
            $accountData,
            $currentOrder,
            $csrString
        );
        
        echo "订单已成功完成！\n";
        echo "证书 URL: " . $finalizedOrder->certificateUrl . "\n";
        
        // 下载证书包
        $certificateBundle = $acmeClient->certificate()->get(
            $accountData,
            $finalizedOrder->certificateUrl
        );
        
        // 保存证书和私钥
        file_put_contents('certificate.pem', $certificateBundle->certificate);
        file_put_contents('fullchain.pem', $certificateBundle->fullchain);
        file_put_contents('private-key.pem', $privateKeyString);
        
        echo "证书已保存到 certificate.pem\n";
        echo "完整证书链已保存到 fullchain.pem\n";
        echo "私钥已保存到 private-key.pem\n";
    }
    
} catch (Exception $e) {
    echo "证书生成错误: " . $e->getMessage() . "\n";
}
```

## 高级用法

### DNS-01 挑战

用于通配符证书或无法进行 HTTP 验证的情况：

```php
// 获取 DNS 挑战数据
$dnsChallenge = $acmeClient->domainValidation()->getValidationData(
    [$validation],
    AuthorizationChallengeEnum::DNS
);

foreach ($dnsChallenge as $challenge) {
    echo "域名 " . $challenge['domain'] . " 的 DNS 挑战:\n";
    echo "  记录名称: " . $challenge['domain'] . "\n";
    echo "  记录类型: TXT\n";
    echo "  记录值: " . $challenge['digest'] . "\n\n";
}

// 添加 DNS 记录后，触发验证
$response = $acmeClient->domainValidation()->validate(
    $accountData,
    $validation,
    AuthorizationChallengeEnum::DNS,
    localTest: true
);
```

### 使用 ARI 进行证书续签

```php
use ALAPI\Acme\Management\RenewalManager;

// 加载现有证书
$certificatePem = file_get_contents('certificate.pem');

// 创建续签管理器
$renewalManager = $acmeClient->renewalManager(defaultRenewalDays: 30);

// 检查是否需要续签
if ($renewalManager->shouldRenew($certificatePem)) {
    echo "证书需要续签\n";
    
    // 如果支持 ARI，获取 ARI 信息
    if ($acmeClient->directory()->supportsARI()) {
        $renewalInfo = $acmeClient->renewalInfo()->getFromCertificate($certificatePem);
        
        echo "建议的续签窗口:\n";
        echo "  开始时间: " . $renewalInfo->suggestedWindow['start'] . "\n";
        echo "  结束时间: " . $renewalInfo->suggestedWindow['end'] . "\n";
        
        if ($renewalInfo->shouldRenewNow()) {
            echo "ARI 建议立即续签\n";
            // 执行续签...
        }
    }
} else {
    echo "证书暂时不需要续签\n";
}
```

### 证书撤销

```php
try {
    // 加载要撤销的证书
    $certificatePem = file_get_contents('certificate.pem');
    
    // 撤销证书
    $success = $acmeClient->certificate()->revoke(
        $certificatePem,
        reason: 1 // 0=未指定, 1=密钥泄露, 2=CA泄露, 3=关联变更, 4=被取代, 5=停止运营
    );
    
    if ($success) {
        echo "证书撤销成功\n";
    } else {
        echo "证书撤销失败\n";
    }
    
} catch (Exception $e) {
    echo "撤销错误: " . $e->getMessage() . "\n";
}
```

### 多证书颁发机构

```php
// Let's Encrypt
$letsEncrypt = new AcmeClient(
    staging: false,
    localAccount: $account,
    httpClient: $httpClient
);

// ZeroSSL
$zeroSSL = new AcmeClient(
    localAccount: $account,
    httpClient: $httpClient,
    baseUrl: 'https://acme.zerossl.com/v2/DV90/directory'
);

// Google Trust Services
$googleCA = new AcmeClient(
    localAccount: $account,
    httpClient: $httpClient,
    baseUrl: 'https://dv.acme-v02.api.pki.goog/directory'
);
```

### 自定义 HTTP 客户端配置

```php
use ALAPI\Acme\Http\Clients\ClientFactory;

$httpClient = ClientFactory::create(30, [
    'proxy' => 'http://proxy.example.com:8080',
    'verify' => true, // SSL 验证
    'timeout' => 30,
    'connect_timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp ACME Client 1.0'
    ]
]);
```

### 日志记录

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 创建日志记录器
$logger = new Logger('acme');
$logger->pushHandler(new StreamHandler('acme.log', Logger::INFO));

// 在客户端上设置日志记录器
$acmeClient->setLogger($logger);
```

## 配置

### 账户管理选项

**使用 AccountStorage 进行基于文件的管理：**

```php
use ALAPI\Acme\Utils\AccountStorage;

// 检查账户文件是否存在
if (AccountStorage::exists('storage', 'my-account')) {
    $account = AccountStorage::loadFromFiles('storage', 'my-account');
} else {
    $account = AccountStorage::createAndSave('storage', 'my-account');
}

// 自动加载或创建账户
$account = AccountStorage::loadOrCreate(
    directory: 'storage',
    name: 'my-account',
    keyType: 'ECC',
    keySize: 'P-384'
);
```

**使用 Account 类处理现有密钥：**

```php
use ALAPI\Acme\Accounts\Account;

// 从现有私钥
$privateKey = file_get_contents('/path/to/private.key');
$account = new Account($privateKey);

// 使用私钥和公钥
$privateKey = file_get_contents('/path/to/private.key');
$publicKey = file_get_contents('/path/to/public.key');
$account = new Account($privateKey, $publicKey);

// 创建新账户并指定密钥类型
$account = Account::createECC('P-384');  // 或 'P-256', 'P-521'
$account = Account::createRSA(4096);     // 或 2048, 3072

// 获取账户信息
echo "密钥类型: " . $account->getKeyType() . "\n";
echo "密钥大小: " . $account->getKeySize() . "\n";
```

## 错误处理

```php
use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Exceptions\AcmeAccountException;
use ALAPI\Acme\Exceptions\DomainValidationException;
use ALAPI\Acme\Exceptions\AcmeCertificateException;

try {
    // ACME 操作
} catch (AcmeAccountException $e) {
    echo "账户错误: " . $e->getMessage() . "\n";
    echo "详情: " . $e->getDetail() . "\n";
    echo "类型: " . $e->getAcmeType() . "\n";
} catch (DomainValidationException $e) {
    echo "验证错误: " . $e->getMessage() . "\n";
} catch (AcmeCertificateException $e) {
    echo "证书错误: " . $e->getMessage() . "\n";
} catch (AcmeException $e) {
    echo "ACME 错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
}
```

## 测试

运行测试套件：

```bash
composer test
```

运行静态分析：

```bash
composer analyse
```

修复代码风格：

```bash
composer cs-fix
```

## 安全考虑

1. **私钥安全**：使用适当的文件权限（600）安全存储私钥
2. **账户密钥**：将账户密钥与证书密钥分开存储
3. **测试环境**：测试时使用 staging 环境
4. **速率限制**：注意 CA 的速率限制
5. **验证**：在触发 ACME 验证之前始终进行本地验证

## 完整示例

以下是一个完整的证书申请示例：

```php
<?php
require_once 'vendor/autoload.php';

use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Utils\AccountStorage;
use ALAPI\Acme\Http\Clients\ClientFactory;
use ALAPI\Acme\Enums\AuthorizationChallengeEnum;
use ALAPI\Acme\Security\Cryptography\OpenSsl;

// 1. 创建或加载账户
$account = AccountStorage::loadOrCreate('storage', 'production-account');

// 2. 初始化客户端
$httpClient = ClientFactory::create(30);
$acmeClient = new AcmeClient(
    staging: false,
    localAccount: $account,
    httpClient: $httpClient
);

try {
    // 3. 注册账户（如果尚未注册）
    try {
        $accountData = $acmeClient->account()->get();
        echo "账户已存在\n";
    } catch (Exception $e) {
        $accountData = $acmeClient->account()->create(
            contacts: ['mailto:admin@yourdomain.com']
        );
        echo "新账户已创建\n";
    }
    
    // 4. 创建证书订单
    $domains = ['yourdomain.com', 'www.yourdomain.com'];
    $order = $acmeClient->order()->new($accountData, $domains);
    echo "订单已创建，状态: " . $order->status . "\n";
    
    // 5. 处理域名验证
    $validations = $acmeClient->domainValidation()->status($order);
    
    foreach ($validations as $validation) {
        if ($validation->isPending()) {
            $domain = $validation->identifier['value'];
            
            // 获取挑战数据
            $challenges = $acmeClient->domainValidation()->getValidationData(
                [$validation],
                AuthorizationChallengeEnum::HTTP
            );
            
            foreach ($challenges as $challenge) {
                $filename = $challenge['filename'];
                $content = $challenge['content'];
                $filePath = "/var/www/html/.well-known/acme-challenge/{$filename}";
                
                // 创建目录
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // 写入挑战文件
                file_put_contents($filePath, $content);
                echo "为域名 {$domain} 创建了挑战文件: {$filePath}\n";
            }
            
            // 触发验证
            $acmeClient->domainValidation()->validate(
                $accountData,
                $validation,
                AuthorizationChallengeEnum::HTTP,
                localTest: true
            );
            echo "已触发域名 {$domain} 的验证\n";
        }
    }
    
    // 6. 等待验证完成
    $maxAttempts = 20;
    $attempt = 0;
    
    do {
        sleep(10);
        $attempt++;
        
        $currentOrder = $acmeClient->order()->get($accountData, $order->url);
        echo "订单状态: " . $currentOrder->status . "\n";
        
        if ($currentOrder->status === 'ready') {
            break;
        }
        
        if ($currentOrder->status === 'invalid') {
            throw new Exception('订单验证失败');
        }
        
    } while ($attempt < $maxAttempts);
    
    if ($currentOrder->status !== 'ready') {
        throw new Exception('验证超时');
    }
    
    // 7. 使用 OpenSsl 助手生成 CSR 和私钥
    $certificatePrivateKey = OpenSsl::generatePrivateKey('RSA', 2048);
    $csrString = OpenSsl::generateCsr($domains, $certificatePrivateKey);
    $privateKeyString = OpenSsl::openSslKeyToString($certificatePrivateKey);
    
    // 8. 完成订单
    $finalizedOrder = $acmeClient->order()->finalize(
        $accountData,
        $currentOrder,
        $csrString
    );
    
    echo "订单已完成！\n";
    
    // 9. 下载证书
    $certificateBundle = $acmeClient->certificate()->get(
        $accountData,
        $finalizedOrder->certificateUrl
    );
    
    // 10. 保存证书和私钥
    $certDir = 'certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
    }
    
    // 保存服务器证书
    file_put_contents("{$certDir}/certificate.pem", $certificateBundle->certificate);
    // 保存完整证书链
    file_put_contents("{$certDir}/fullchain.pem", $certificateBundle->fullchain);
    // 保存私钥
    file_put_contents("{$certDir}/private-key.pem", $privateKeyString);
    
    // 设置安全的文件权限
    chmod("{$certDir}/private-key.pem", 0600);
    
    echo "完整证书链已保存到 {$certDir}/fullchain.pem\n";
    echo "私钥已保存到 {$certDir}/private-key.pem\n";
    
    // 11. 清理挑战文件
    foreach ($validations as $validation) {
        $challenges = $acmeClient->domainValidation()->getValidationData(
            [$validation],
            AuthorizationChallengeEnum::HTTP
        );
        
        foreach ($challenges as $challenge) {
            $filename = $challenge['filename'];
            $filePath = "/var/www/html/.well-known/acme-challenge/{$filename}";
            if (file_exists($filePath)) {
                unlink($filePath);
                echo "已清理挑战文件: {$filePath}\n";
            }
        }
    }
    
    echo "证书申请完成！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
```

## 贡献

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 为新功能添加测试
5. 运行测试套件
6. 提交 pull request

## 许可证

本项目基于 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件。

## 链接

- [Let's Encrypt 文档](https://letsencrypt.org/docs/)
- [ACME 规范 RFC 8555](https://tools.ietf.org/html/rfc8555)
- [ZeroSSL ACME 指南](https://zerossl.com/documentation/acme/)

## 支持

如果遇到任何问题或有疑问：

1. 查看[文档](#快速开始)
2. 搜索现有的 [issues](https://github.com/anhao/acme-client/issues)
3. 如需要可创建 [新 issue](https://github.com/anhao/acme-client/issues/new)
