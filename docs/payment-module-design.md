# payment 模块后端设计（Phalcon Admin）

> 状态：设计草案，不包含代码实现（仅给出目录、表结构、接口契约与关键代码骨架）
> 上游设计：前端 tao996 支付与权益模块设计（[packages/tao996/docs/payment-module-design.md](../../../packages/tao996/docs/payment-module-design.md)）
> 后端架构约定：[REASONIX.md](../REASONIX.md) 与 [docs/architecture.md](./architecture.md)

本文档为前端的支付/权益体系（§12 服务端职责）在 Phalcon Admin 后端的落地设计。核心原则与上游一致：**客户端不得持有敏感凭证、客户端上报状态不可作为权益事实来源、权益由服务端从交易与授权账本重建、统一订单幂等、稳定错误码**。

---

## 1. 模块定位与边界

后端 payment 作为 `tao` 模块的**支付子域**，承担上游设计 §12 的全部服务端职责：

- 渠道无关的统一订单与幂等；
- 微信/支付宝（自有商户）/Apple IAP / Google Play / RevenueCat 的验单与权益发放；
- 权益快照签发（带签名）、订阅周期管理、试用一次性发放与防重领；
- webhook 验签、幂等落库、重放保护。

**与现有 `A0/open` 的关系**：`A0/open` 已存在微信支付实现（`OpenOrder`、`WepayOrderLogic`、`Helper/wepay/*`）。本模块**演进复用**其微信支付能力，不另起炉灶：

- 微信支付渠道统一走 `A0/open` 的 `WepayServer`/`WepayOrderLogic`；
- 新增的通用层（`payment`）负责抽象渠道、权益账本与上游接口，微信渠道内部委托 `A0/open`；
- 支付宝、Apple、Google、RevenueCat 为新实现，放在 `payment` 下。

---

## 2. 目录结构

建议作为 `tao` 模块下的子模块（路由 `/m/tao.payment/...`，匹配现有"子模块点号"约定）：

```text
src/App/Modules/tao/A0/payment/
├── Module.php                      # 子模块入口（可选，路由可由 tao 模块统一注册）
├── Config/
│   └── Config.php                  # 表前缀等常量（继承 tao 的 Config::TABLE_PREFIX）
├── Controllers/
│   ├── OfferingsController.php     # /m/tao.payment/offerings  (GET)
│   ├── OrdersController.php        # /m/tao.payment/orders      (POST/GET {id})
│   ├── VerifyController.php        # /m/tao.payment/verify      (POST)
│   ├── RestoreController.php       # /m/tao.payment/restore     (POST)
│   ├── EntitlementController.php   # /m/tao.payment/entitlements/snapshot (GET)
│   ├── TrialController.php         # /m/tao.payment/trials/{activate,status}
│   └── Webhook/
│       ├── AppleController.php
│       ├── GoogleController.php
│       ├── RevenuecatController.php
│       ├── WechatController.php    # 委托 A0/open 验签能力
│       └── AlipayController.php
├── Models/
│   ├── PaymentOrder.php            # 统一订单 + 幂等键
│   ├── ProviderTransaction.php     # 渠道交易原文 / 归一化状态 / 唯一交易ID
│   ├── PaymentSubscription.php     # 订阅周期/续费/宽限期/暂停/取消
│   ├── EntitlementGrant.php        # 权益事实账本
│   ├── EntitlementSnapshot.php     # 签发版本 + 签名 + 审计
│   ├── TrialGrant.php              # 试用实例（once-per-account）
│   └── WebhookEvent.php            # 原始事件 + 验签结果 + 处理状态
├── Services/
│   ├── PaymentService.php          # 下单/验单/幂等编排
│   ├── ProviderService.php         # 渠道注册/互斥组校验（对应前端 §7.1）
│   ├── EntitlementService.php      # 快照签发/重建/合并（对应前端 §11.4）
│   ├── TrialService.php            # 试用资格/激活/防重领
│   ├── SignatureService.php        # 快照签名/校验（HMAC，密钥走 .env）
│   └── WebhookService.php          # webhook 接收/幂等/派发
├── Logic/
│   ├── AppleIapVerifier.php
│   ├── GooglePlayVerifier.php
│   ├── RevenuecatVerifier.php
│   └── AlipayVerifier.php
├── Helper/
│   └── ProductCatalog.php          # offerings 数据源（内部 product id → 渠道 product id 映射）
└── migrations/
    └── v1_payment.php              # 通过 MigrationService::upgrade 注册
```

> 命名空间：`App\Modules\tao\A0\payment\{Controllers,Models,Services,Logic,Helper}`。
> 表前缀沿用 `tao_`（`BaseTaoModel` 的 `Config::TABLE_PREFIX`）。

---

## 3. 数据模型（对应上游 §12）

所有模型继承 `BaseTaoModel`（自带 `id`/`created_at`/`updated_at`/`deleted_at`）。字段风格对齐现有 `OpenOrder`（int 状态常量 + `Map*` 文本映射）。

> **多租户预留**：当前单租户阶段 `tenant_id` 默认 `0`；一旦引入多租户，所有 payment 表 `tenant_id` 必须为非空且参与唯一键与查询（见上游 §12 备注与全局架构不变量）。下方模型已预留 `tenant_id` 字段。

### 3.1 `tao_payment_orders`（统一订单 + 幂等键）

```php
class PaymentOrder extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $user_id = 0;
    public string $idempotency_key = ''; // 客户端生成的幂等键（唯一）
    public string $order_no = '';        // 内部订单号（唯一，对外）
    public string $provider = '';        // revenuecat|appleIap|googlePlay|wechatPay|alipay
    public string $provider_product_id = '';
    public int $amount = 0;              // 分
    public string $currency = 'CNY';
    public int $status = self::StatusCreated; // 10待支付 20成功 30已关闭 40失败
    public string $metadata = '';       // JSON: 内部 product id 等
    public int $paid_at = 0;

    public const int StatusCreated = 10;
    public const int StatusPaid = 20;
    public const int StatusClosed = 30;
    public const int StatusFailed = 40;
}
```

唯一索引：`(tenant_id, idempotency_key)`、`(tenant_id, order_no)`。下单前先按 `idempotency_key` 查重，命中则直接返回原订单（上游 §7 幂等要求）。

### 3.2 `tao_provider_transactions`（渠道交易原文）

```php
class ProviderTransaction extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $order_id = 0;
    public string $provider = '';
    public string $provider_txn_id = '';  // 渠道侧唯一交易ID（唯一键）
    public string $normalized_status = ''; // completed|pending|cancelled|failed|refunded
    public string $raw_payload = '';       // 渠道原始回调（加密存储或摘要）
    public string $signature_ok = '0';     // 验签结果 1/0
    public int $event_at = 0;
}
```

唯一索引：`(tenant_id, provider, provider_txn_id)` —— 重放保护（同交易二次 webhook 直接幂等返回）。

### 3.3 `tao_payment_subscriptions`（订阅）

```php
class PaymentSubscription extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $user_id = 0;
    public int $order_id = 0;
    public string $provider = '';
    public string $provider_txn_id = '';
    public string $product_id = '';
    public string $period = '';          // monthly|yearly
    public int $current_period_start = 0;
    public int $current_period_end = 0;
    public int $cancel_at = 0;           // 0=未取消
    public int $grace_until = 0;         // 宽限期截止
    public int $paused = 0;              // 0/1
    public string $status = '';          // active|canceled|grace|paused|expired
}
```

### 3.4 `tao_entitlement_grants`（权益事实账本）

```php
class EntitlementGrant extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $user_id = 0;
    public string $feature_id = '';
    public string $source = '';          // purchase|subscription|trial|admin
    public string $source_ref = '';      // 关联 order_id / subscription_id / trial_id
    public string $tier_id = '';
    public int $granted_at = 0;
    public int $starts_at = 0;
    public int $expires_at = 0;          // 0=永久
    public string $limits = '';          // JSON: {"exports_per_month":100}
    public int $revoked = 0;             // 退款/撤销后置 1
}
```

> 与上游 §6.3 的 `EntitlementGrant` 字段对齐；`limits` 仅存可序列化类型。

### 3.5 `tao_entitlement_snapshots`（签名快照）

```php
class EntitlementSnapshot extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $user_id = 0;
    public int $revision = 0;            // 每次重算递增
    public string $signature = '';       // HMAC-SHA256(body, 密钥)
    public int $issued_at = 0;
    public int $expires_at = 0;          // 离线可用最晚时间（上游 §13）
    public string $payload = '';         // JSON: EntitlementState（tiers/features/limits）
}
```

签发：每次权益变动 → 重算 `EntitlementState` → `revision+1` → `SignatureService` 签名 → 写表并返回客户端。客户端校验签名、`issued_at`、`expires_at`、`revision`（上游 §13）。

### 3.6 `tao_trial_grants`（试用实例）

```php
class TrialGrant extends BaseTaoModel
{
    public int $tenant_id = 0;
    public int $user_id = 0;
    public string $policy_id = '';       // 对应上游 TrialPolicy.id
    public string $tier_id = '';
    public int $starts_at = 0;
    public int $ends_at = 0;
    public string $server_signature = ''; // 服务端签发，防重领（上游 §11.3）
}
```

唯一索引：`(tenant_id, user_id)` 上的业务约束 —— 同一账号最多一条有效 `trial_grant`，实现 once-per-account（上游 §11.2）。

### 3.7 `tao_webhook_events`（原始事件）

```php
class WebhookEvent extends BaseTaoModel
{
    public int $tenant_id = 0;
    public string $provider = '';
    public string $event_id = '';        // 渠道事件ID（幂等）
    public string $event_type = '';
    public string $raw_body = '';
    public string $signature_ok = '0';
    public string $handled = '0';        // 0待处理 1成功 2失败
    public int $handled_at = 0;
}
```

---

## 4. 接口契约（对应上游 §12 端点）

统一通过 `BaseRbacController` 的 `isLogin()` 鉴权；返回 JSON（API 请求隐式关闭视图）。错误码见 §7。

| 方法 & 路径 | 控制器 | 说明 |
|---|---|---|
| `GET /m/tao.payment/offerings` | `OfferingsController` | 返回内部 product 目录（含渠道 product id 映射，上游 §5/§7） |
| `POST /m/tao.payment/orders` | `OrdersController` | 创建统一订单，携带 `idempotency_key` 幂等 |
| `GET /m/tao.payment/orders/{id}` | `OrdersController` | 订单状态查询 |
| `POST /m/tao.payment/verify` | `VerifyController` | 客户端提交渠道回执（IAP/RevenueCat），服务端验单 |
| `POST /m/tao.payment/restore` | `RestoreController` | 恢复历史购买（IAP） |
| `GET /m/tao.payment/entitlements/snapshot` | `EntitlementController` | 返回签名快照（上游 §13） |
| `POST /m/tao.payment/trials/activate` | `TrialController` | 激活试用（once-per-account 校验） |
| `GET /m/tao.payment/trials/status` | `TrialController` | 试用资格查询 |
| `POST /m/tao.payment/webhooks/{provider}` | `Webhook/*` | 渠道异步通知，验签 + 幂等 |

### 4.1 关键流程：验单发放权益（`POST /verify`）

```text
客户端提交 { provider, product_id, receipt/token, order_no? }
  → ProviderService 定位渠道验证器（AppleIapVerifier / RevenuecatVerifier ...）
  → 验证器向渠道服务端校验（Apple/Google/RC 验证票据；微信/支付宝走 A0/open 查询）
  → 成功：写入 ProviderTransaction（signature_ok=1）
  → PaymentService 创建/更新 PaymentOrder（status=Paid）
  → 若是订阅：写入/更新 PaymentSubscription
  → EntitlementService 计算并写入 EntitlementGrant
  → EntitlementService 重算快照 → SignatureService 签名 → 返回 snapshot
  → 任何失败：返回稳定错误码（§7），不发放权益
```

> **客户端不可信**：验单必须服务端向渠道校验，绝不直接信任客户端回执内容（上游 §1、§18）。Apple/Google 私有密钥、微信/支付宝商户私钥仅存服务端 `.env`，绝不通过任何接口下发（上游 §12）。

---

## 5. 渠道注册与互斥组（对应上游 §7.1）

后端用 `ProviderService` 维护已启用渠道与互斥组，机器可读对应前端 `providerExclusiveGroups`：

```php
const array PROVIDER_EXCLUSIVE_GROUPS = [
    'revenueCat' => ['appleInApp', 'googleInApp'],
    'appleIap'   => ['appleInApp'],
    'googlePlay' => ['googleInApp'],
    'wechatPay'  => [],
    'alipay'     => [],
];
```

同一租户下不能同时启用 `revenueCat` 与 `appleIap`（同属 `appleInApp`），避免同一 Apple 交易被两个通道重复处理。冲突在配置加载时抛 `ProviderConflictException`。

---

## 6. 安全与合规（对应上游 §1、§12、§18）

- **凭证隔离**：渠道私钥/密钥存 `.env`，经 Symfony Dotenv 加载到 `$_ENV`；不进代码、不进日志、不下发客户端（上游 §18）。
- **webhook 验签**：每个 `Webhook/*` 先验签再处理；验签失败直接 4xx/丢弃，记录 `WebhookEvent.signature_ok=0`（上游 §12）。
- **幂等**：订单用 `idempotency_key`，交易用 `(tenant_id, provider, provider_txn_id)`，事件用 `event_id`（上游 §7）。
- **授权分层**：Web 后台管理界面（RBAC `@rbac`）与对外支付 API 分离；敏感操作（发放/撤销权益）必须由服务端逻辑触发，不能由客户端参数直接驱动（上游 §9、§18）。
- **日志脱敏**：不得在日志打印完整回执、私钥、`server_signature`。

RBAC 示例（后台运维入口）：

```php
/**
 * @rbac({title:'支付管理'})
 */
class OrdersController extends BaseRbacController { /* indexAction 列表等 */ }
```

---

## 7. 稳定错误码（对应上游 §15、§20）

对外返回 JSON 统一带 `code`（稳定枚举值，不随文案变化）；埋点事件也携带该 code（上游 §20）。

| code | 含义 | HTTP |
|---|---|---|
| `payment_order_duplicate` | 幂等键重复 | 200（返回原单） |
| `payment_provider_unavailable` | 渠道校验失败/渠道不可用 | 502 |
| `payment_receipt_invalid` | 回执校验不通过 | 400 |
| `payment_signature_invalid` | 快照签名校验失败 | 400 |
| `trial_already_used` | 账号已用过试用 | 409 |
| `entitlement_access_denied` | 权益不足 | 403 |
| `server_error` | 内部错误 | 500 |

```json
{ "code": "payment_receipt_invalid", "msg": "凭证校验未通过", "trace": "..." }
```

> 枚举值上线后**不得重命名/删除**（仅新增），与上游 §22 稳定 ID 原则一致。

---

## 8. 数据库迁移

通过现有 `MigrationService::upgrade(version, summary, callable)` 注册（见 `Services/MigrationService.php`）：

```php
MigrationService::upgrade('v1_payment', '创建支付与权益表', function (\PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS tao_payment_orders (...)");
    // ... 其余 7 张表
});
```

> 迁移文件放在 `A0/payment/migrations/v1_payment.php`，由部署流程或 `php artisan migration` 执行。注意 `MigrationService` 说明：DDL 避免包裹在会触发隐式事务的语句中。

---

## 9. 测试建议（对应上游 §19）

- **单元**：`SignatureService` 签名/校验；`EntitlementService` 多来源合并（上游 §11.4）；`ProviderService` 互斥组冲突；`TrialService` once-per-account 防重领（含离线边界，上游 §11.3）。
- **集成**：`POST /verify` 用渠道沙箱/Mock 验证器；webhook 重放幂等（`event_id` 二次投递返回相同结果）。
- **安全**：断言凭证不出现在响应/日志；`idempotency_key` 重复返回原单。

测试框架遵循 REASONIX.md：PHPUnit 11 + Mockery，phax 测试用 `src/phpunit.example.xml`。

---

## 10. 决策摘要

1. payment 作为 `tao` 模块子域 `A0/payment`，复用 `A0/open` 微信支付能力，避免重复实现。
2. 8 张表对应上游 §12；模型继承 `BaseTaoModel`，字段风格对齐 `OpenOrder`。
3. 统一订单幂等（idempotency_key）+ 交易唯一键 + 事件幂等，杜绝重复发放。
4. 权益由服务端从交易/账本重建，客户端回执仅作校验输入，不作为事实来源。
5. 凭证隔离 `.env`，webhook 验签，稳定错误码，RBAC 分离后台与 API。
6. 预留 `tenant_id`，对齐全局多租户不变量。
7. 渠道互斥组机器可读，防止同交易多通道重复处理（上游 §7.1）。
