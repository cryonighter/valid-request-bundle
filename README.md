# Valid Request Bundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Total Downloads][ico-downloads]][link-downloads]

Symfony bundle for building and validating objects from HTTP request data.

Основная идея этого бандла — валидация не объекта DTO, а непосредственно пользовательского ввода.
А свойства объекта выступают просто местом описания запроса.

Если использовать стандартный механизм `ArgumentResolver`/`ValueResolver` для создания экземпляров каждого DTO, 
описывать правила валидации через атрибуты Symfony Assert, и в дальнейшем валидировать его через стандартный сервис
симфони валидатора — возникали ряд принципиальных ограничений:

1. Кейсы "поля вообще нет в запросе" и "поле имеет пустое значение" принципиально неотличимы, ведь валидируется уже собранный объект.
2. При использовании типизированных свойств класса исходное значение, переданное пользователем, может потеряться и быть провалидировано неправильно.
Например, если в классе свойство имеет тип int, а пользователь ввел строковое значение "two", то нужно будет привести его к значению 0,
чтоб просто собрать объект для валидации. В результате чего будет провалидировано наше значение 0, а пользователь не получит ошибки 
или получит ее не на то значение, которое он вводил.
3. Ну и просто писать десятки и сотни резольверов на каждое DTO в проекте — это неудобно. Особенно если у них есть вложенные объекты.

Те же проблемы (кроме, наверное, пункта 3) актуальны и для нативных атрибутов маппинга
данных Symfony (`#[MapRequestPayload]`, `#[MapQueryString]`).

Для решения этих проблем и появился `ValidRequestBundle`. Он действует по следующему алгоритму:
1. Анализирует структуру DTO, выбирая из него свойств констрейнты, минимально необходимые для сборки объекта.
К таковым относятся `Assert\NotNull`, `Assert\NotBlank`, `Assert\Type` и `Assert\Choice`, а так же `Assert\All` с
перечисленными выше констрейнтами. Если у свойства нет таких констрейнтов - добавляет их, ориентируясь на тайпхинт
свойства. Из собранных/добавленных констрейнтов формируется констрейнт `Assert\Collection`, полностью повторяющий
структуру анализируемого DTO.
2. Подготавливает данные для гидрации объекта. Собирает данные из HTTP запроса в единый ассоциативный массив,
структура которого полностью повторяет структуру DTO. Так же на этапе подготовки данных к ним могут применяться
различные преобразования, например, приведение типа, установка дефолтного значения и т.д.
3. Валидирует данные, собранные в пункте 2, при помощи констрейнтов, собранных в пункте 1. Для этого
используется стандартный валидатор симфони.
4. Если валидация прошла успешно и ошибок нет — запускается гидрация DTO. 
5. Получившийся объект отправляется в стандартный валидатор симфони, где проходит валидацию
по всем описанным в атрибутах констрейнтам.
6. Если валидация снова прошла успешно — объект внедряется в метод контроллера.
7. Если валидация в пункте 3 или 5 не прошла, и есть ошибки — они будут переданы для обработки либо в метод
контроллера (если он принимает список ошибок валидации в качестве своего аргумента), либо в виде эксепшна.

Данный алгоритм не является исчерпывающим и описан скорее для понимания идеи, которую реализует бандл.


## Highlights

- Symfony Framework Bundle
- Composer ready, [PSR-2][] and [PSR-4][] compliant
- Minimal dependencies — all work is done through reflection
- Minimal number of attributes and interfaces — maximum use of class description and Symfony constraints

## Requirements

- **PHP >= 8.4.0** but the latest stable version of PHP is recommended
- **Symfony >= 6.0** but the latest stable version of Symfony is recommended

## Install

Via Composer

```shell script
composer require cryonighter/valid-request-bundle
```

The bundle will be automatically registered in `config/bundles.php`:

```php
return [
    // ...
    Cryonighter\ValidRequestBundle\ValidRequestBundle::class => ['all' => true],
];
```


## Usage

### Basic example

Просто опишите класс с правилами валидации через Symfony Assert и пометьте его атрибутом `#[ValidRequest]`.
Бандл автоматически соберёт объект из данных HTTP-запроса, провалидирует его и передаст в контроллер уже готовым к использованию.

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class AuthUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $login,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
    ) {}
}
```

Если вы хотите использовать логику атрибута `#[ValidRequest]` не везде, а только для некоторых аргументов контроллера,
допускается указание этого атрибута непосредственно к аргументу контроллера. Кроме того, установка атрибута на
аргументах контроллера позволяет установить разным методам контроллеров разные группы (см. ниже).

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

class AuthController
{
    #[Route('/user/auth', methods: [Request::METHOD_POST])]
    public function auth(
        #[ValidRequest] AuthUserRequest $authUserRequest,
    ): Response {
        // ...
    }
}
```

Атрибут `#[ValidRequest]` на аргументе контроллера имеет более высокий приоритет, чем установленный на классе.


### Nullable arguments

Если аргумент контроллера объявлен как nullable, то при отсутствии данных в запросе в него будет передан `null`.
Это позволяет одним и тем же экшнам, например, обслуживать одновременно отображение и обработку формы:

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

class AuthController
{
    #[Route('/user/auth', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function auth(
        #[ValidRequest] ?AuthUserRequest $authUserRequest,
    ): Response {
        if ($authUserRequest) {
            // Auth user
        } else {
            // Render auth form
        }
    }
}
```

Отсутствием данных считается ситуация, когда в запросе нет **ни одного** поля, описанного в DTO.
При этом:

- **Значения по умолчанию не считаются входными данными.** Если у DTO есть обязательные поля
  и поля со значениями по умолчанию, а запрос пуст, аргумент получит `null`.
  Значения по умолчанию в валидацию не попадут, и ошибок на обязательные поля не будет.
- **Любое поле DTO в запросе считается вводом**, даже если оно пустое, равно `null`
  или совпадает со значением по умолчанию. В этом случае все данные проходят валидацию, как обычно.
- **Учитываются все источники данных**, а не только основной. Поле, которое пришло
  из другого источника через `#[BindRequestParam(source: ...)]`, тоже считается вводом.
- **Патчеры тоже могут быть источником ввода.** Если патчер подставил данные, которых нет в запросе,
  аргумент получит не `null`, а собранный объект (или ошибки валидации).
- **Поля запроса, которых нет в DTO, на результат не влияют.**

Если аргумент объявлен как not nullable, `null` в него не передаётся никогда. При пустом запросе
данные из значений по умолчанию валидируются, и вы получите ошибки на обязательные поля.


### Синтез констрейнтов

Если для свойств классов заданы тайпхинты, но не заданы констрейнты `Assert\NotNull`, `Assert\NotBlank`, `Assert\Type`,
Бандл синтезирует их автоматически по следующем правилам:

**Валидация типа**:
Если в качестве тайпхинта указан backed enum — добавляется констрейнт `Assert\Choice`, а в его параметр `choices`
передается значения метода `cases()` этого enum. Констрейнт `Assert\Type` не добавляется.

Если в качестве тайпхинта указан класс — добавляется констрейнт `Assert\Type`, а в его параметр `type`
передается `array`, так как в PHP данные приходят в виде ассоциативных массивов, которыми потом гидрируются объекты.

Во всех остальных случаях добавляется констрейнт `Assert\Type`,
а в его параметр `type` передается значение тайпхинта свойства.

**Валидация nullable**:
Если свойство имеет not nullable тип и не имеет констрейнта `Assert\NotBlank` - добавляется констрейнт `Assert\NotNull`.

Например, такой вот класс:
```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        public ?int $age,
        public Status $status,
        public ?Address $address = null,
    ) {}
}
```

С точки зрения бандла будет выглядеть вот так:
```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $name,

        #[Assert\Type('int')]
        public ?int $age,

        #[Assert\NotNull]
        #[Assert\Choice(choices: Status::cases())]
        public Status $status,

        #[Assert\Type('array', message: 'This value should be of type object.')]
        public ?Address $address = null,
    ) {}
}
```


### Ошибки валидации

По умолчанию при провале валидации бандл выбрасывает стандартное исключение `ValidationFailedException`
из пакета `symfony/validator` до передачи управления контроллеру.

Перехватить его можно через стандартные механизмы Symfony — `Event Listener` или `Event Subscriber`,
и вернуть пользователю удобный ответ с описанием ошибок.

Сам бандл никак не обрабатывает ошибки валидации и не предоставляет никаких обработчиков ошибок.
Разве что передать список ошибок валидации в контроллер (об этом ниже).

Поскольку, как отмечалось вначале, главная идея бандла заключается именно в валидации пользовательского ввода,
пути к ошибкам валидации будут содержать имена полей в http запросе, а не имена свойств в объекте DTO.
Если они отличаются — будет выполнено преобразование в пользу полей запроса.


### Передача ошибок валидации в контроллер

Если вы хотите обрабатывать ошибки валидации непосредственно в контроллере, добавьте следующим
аргументом экземпляр `ConstraintViolationListInterface` (из пакета `symfony/validator`).
В этом случае исключение выброшено не будет — вместо этого аргумент получит список ошибок валидации предшествующего аргумента.

> **Порядок аргументов важен!** Контроллер может принимать несколько аргументов, помеченных атрибутом `#[ValidRequest]`,
> и именно их последовательность определяет, какой список ошибок к какому объекту относится.

Этот механизм работает независимо от того, где указан атрибут `#[ValidRequest]` — на классе или на аргументе контроллера.

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class AuthController
{
    #[Route('/user/auth', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function auth(
        ?AuthUserRequest $authUserRequest,
        ?ConstraintViolationListInterface $violations,
    ): Response {
        // ...
    }
}
```

Аргумент типа `ConstraintViolationListInterface` так же можно указать как `nullable`. Он примет значение null,
если валидация прошла успешно и ошибок нет, или если валидация не проводилась (например, при GET-запросе).

Если указать его как `not nullable`, то при отсутствии ошибок валидации будет передан пустой список.


### Источники данных

По умолчанию данные берутся из тела запроса (`POST`, `PUT`, `PATCH`).
Для использования других источников укажите параметр `source` в атрибуте `#[ValidRequest]`:

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;

#[ValidRequest(source: RequestValueSource::query)]
class GoodsFilterRequest
{
    // ...
}
```

Доступные источники данных:

| Источник     | Описание                                                    |
|--------------|-------------------------------------------------------------|
| `payload`    | Тело запроса (`POST`, `PUT`, `PATCH`) — **по умолчанию**    |
| `query`      | Query-параметры (`GET`)                                     |
| `attributes` | Атрибуты Symfony Request                                    |
| `cookies`    | Cookies                                                     |
| `files`      | Загружаемые файлы (`multipart/form-data`)                   |
| `headers`    | Заголовки запроса                                           |
| `server`     | Серверные переменные                                        |

Источник данных для отдельного свойства класса можно переопределить через атрибут `#[BindRequestParam]` (см. ниже).


### Значения по умолчанию

Если свойство класса имеет значение по умолчанию, поле в запросе становится необязательным.
При отсутствии поля в запросе свойство примет значение по умолчанию.

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

#[ValidRequest]
class SearchRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $query,

        public readonly string $sort = 'created_at',

        public readonly int $limit = 20,

        public readonly ?string $filter = null,
    ) {}
}
```

**Важно:** значение по умолчанию применяется **только если поле полностью отсутствует** в запросе.
Если поле присутствует в запросе — значение по умолчанию игнорируется, даже если переданное "пустое" значение:

- Если свойство `nullable`, а поле передано с явным `null` (JSON) или без значения (form) — свойство получит значение `null`.
- Если свойство типа `string`, а поле передано с явной пустой строкой — свойство получит `''`.

Таким образом, бандл всегда точно различает «поле не пришло» и «поле пришло с пустым значением».


### Вложенные объекты

В простом случае класс вложенного объекта определяется автоматически по типу свойства,
и этого достаточно, когда тип — конкретный инстанцируемый класс и именно он нам и нужен.

Если тип объявлен как nullable — то при отсутствии данных в запросе свойству будет присвоено значение `null`.

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

class Settings
{
    public function __construct(
        public string $lang,
    ) {}
}

#[ValidRequest]
class ProfileUserRequest
{
    public function __construct(
        #[Assert\Valid]
        public ?Settings $settings,
    ) {}
}
```

Но тип свойства не всегда однозначно задаёт класс для сборки. Он может быть интерфейсом,
абстрактным классом, `object`, `mixed` или отсутствовать вовсе (что приравнивается к `mixed`).
Либо быть базовым классом, тогда как вам нужен конкретный наследник. Во всех этих случаях
целевой класс задаётся явно через `Assert\Type`, а при необходимости выбирается по группе:

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class CheckoutRequest
{
    public function __construct(
        // Тип свойства — интерфейс. Конкретный класс берётся из Assert\Type.
        #[Assert\Type(type: CardPayment::class)]
        #[Assert\Valid]
        public PaymentMethodInterface $method,

        // Тип свойства — базовый класс, но нужен именно наследник.
        #[Assert\Type(type: EmailNotification::class)]
        #[Assert\Valid]
        public Notification $notification,

        // Несколько Assert\Type с группами: класс выбирается по активной группе.
        // Для группы Card — CardPayment, для группы Crypto — CryptoPayment.
        #[Assert\Type(type: CardPayment::class, groups: ['Card'])]
        #[Assert\Type(type: CryptoPayment::class, groups: ['Crypto'])]
        #[Assert\Valid]
        public AbstractPaymentMethod $refund,

        // Тип mixed: одна группа собирает объект, другая оставляет примитивный id.
        #[Assert\Type(type: CardPayment::class, groups: ['Full'])]
        #[Assert\Type(type: 'int', groups: ['Ref'])]
        public mixed $source,
    ) {}
}
```

Правила разрешения типа:

- `Assert\Type` **перекрывает** тип свойства, но только если он активен для текущего набора групп.
- `Assert\Type` без группы применяется всегда, с группой — только когда эта группа активна.
- Если ни один `Assert\Type` не активен, класс берётся из типа свойства (при условии, что он инстанцируемый).
- Если `Assert\Type` указывает на примитивный тип (например `int`) и тип свойства его поддерживает (например `mixed`),
  значение остаётся примитивом и объект не собирается.

#### Исключения

В ситуациях, когда итоговый тип невозможно использовать для создания объекта, бандл выбрасывает
исключение `ValidRequestException`. Такими ситуациями являются:

- тип свойства неинстанцируемый (интерфейс, абстрактный класс, `object`, `mixed` или его отсутствие),
  и при этом ни один `Assert\Type` не определен или не активен для текущих групп.
- активный `Assert\Type` указывает на интерфейс или абстрактный класс.
- тип свойства — объектный, а активный `Assert\Type` задаёт примитивный тип.


### Вложенные массивы объектов

Для массивов объектов действуют те же принципы, что и для объектов, но указания типа
элементов массива атрибут `Assert\Type` указывается внутри атрибута `Assert\All`:

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

class Contact
{
    public function __construct(
        #[Assert\Choice(choices: ['email', 'phone'])]
        public string $type,

        #[Assert\Length(min: 5, max: 100)]
        public string $value,
    ) {}
}

#[ValidRequest]
class ProfileUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\NotBlank(),
            new Assert\Type(Contact::class),
        ])]
        #[Assert\Valid]
        public array $contacts,
    ) {}
}
```

Поскольку `Assert\All` применяет вложенные констрейнты к каждому элементу массива независимо от его ключа,
точно так же поддерживаются и ассоциативные массивы объектов с произвольными строковыми ключами.
Строковые ключи сохраняются при сборке, а в путях к ошибкам валидации используется соответствующий ключ,
например `contacts[work].value`. Никакой дополнительной настройки для этого не требуется.


### Массивы объектов разных типов

Иногда одно и то же свойство-массив должно содержать объекты разных классов в зависимости
от активной группы валидации. Для этого укажите внутри `Assert\All` несколько `Assert\Type`,
каждый со своим целевым классом и своей группой. Бандл соберёт массив из инстансов того класса,
чей `Assert\Type` активен для текущего набора групп.

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

class Wizard
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $name,

        #[Assert\Range(min: 1, max: 100)]
        public int $spellPower,
    ) {}
}

class Warrior
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $name,

        #[Assert\Positive]
        public int $strength,
    ) {}
}

#[ValidRequest]
class PartyRequest
{
    public function __construct(
        #[Assert\All([
            new Assert\Type(type: Wizard::class, groups: ['Wizards']),
            new Assert\Type(type: Warrior::class, groups: ['Warriors']),
        ])]
        #[Assert\Valid]
        public array $party,
    ) {}
}
```

Для группы `Wizards` массив будет собран из объектов `Wizard`, а для группы `Warriors` — из объектов `Warrior`:

```php
#[Route('/party/wizards', methods: [Request::METHOD_POST])]
public function createWizards(
    #[ValidRequest(groups: ['Default', 'Wizards'])]
    PartyRequest $request,
): Response {
    // $request->party — массив инстансов Wizard
}

#[Route('/party/warriors', methods: [Request::METHOD_POST])]
public function createWarriors(
    #[ValidRequest(groups: ['Default', 'Warriors'])]
    PartyRequest $request,
): Response {
    // $request->party — массив инстансов Warrior
}
```

**Каскадная валидация и группы.**
Атрибут `Assert\Type` проверяет только тип элемента и не заходит внутрь объекта — за каскадную проверку
вложенных полей отвечает `Assert\Valid`. Если у полей ваших вложенных классов не указаны группы валидации — они
по умолчанию находятся в группе `Default`. Поэтому для осуществления каскадной валидации вам стоит либо не указывать
группы валидации у `Assert\Valid` (как это показано на примере), либо указать группу `Default` явно:
```php
#[Assert\Valid(groups: ['Default', 'Wizards', 'Warriors'])]
public array $party;
```

Так же не стоит забывать указывать группу `Default` в атрибуте `#[ValidRequest]` (как на примере), чтобы
валидировались все свойства без явного указания групп. В противном случае валидация для них выполнена не будет.

> Если передано сразу несколько групп и сразу несколько одинаковых атрибутов
> им соответствуют — применяется первый попавшийся, остальные игнорируются.


### Атрибут BindRequestParam

Атрибут `#[BindRequestParam]` позволяет переопределить стандартное поведение при связывании данных запроса со свойствами класса.

#### Параметр "name"

Если имя поля в запросе отличается от имени свойства класса, укажите его явно:

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class ProfileUserRequest
{
    public function __construct(
        #[BindRequestParam(name: 'id')]
        #[Assert\Positive]
        public int $userId,
    ) {}
}
```

#### Параметр "source"

Позволяет задать источник данных для конкретного свойства, переопределяя источник, указанный в `#[ValidRequest]`:

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class ProfileUserRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::attributes)]
        #[Assert\Language]
        public string $lang,
    ) {}
}
```


### Группы валидации

Группы валидации передаются через параметр `groups` атрибута `#[ValidRequest]`,
аналогично передаче групп напрямую в сервис валидатора:

```php
#[ValidRequest(groups: ['Default', 'Create'])]
```

Атрибут `#[BindRequestParam]` так же реагирует на группы, указанные через `#[ValidRequest]`.
Чтобы указать к каким группам его применять — нужно перечислить их в параметре `groups`
по аналогии с группами в атрибутах `#[Assert]`.

В примере ниже код страны будет извлечён из атрибутов запроса для группы `Update`,
а для группы `Create` и всех остальных будет использован источник по умолчанию (тело запроса):

```php
class CountryRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::attributes, groups: ['Update'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $code,

        #[Assert\NotBlank]
        public string $name,
    ) {
    }
}

class AuthController
{
    #[Route('/country', methods: [Request::METHOD_POST])]
    public function create(
        #[ValidRequest(groups: ['Default', 'Create'])]
        ?CountryRequest $countryRequest,
    ): Response {
        // ...
    }

    #[Route('/country/{code}', methods: [Request::METHOD_PUT])]
    public function update(
        string $code,

        #[ValidRequest(groups: ['Default', 'Update'])]
        ?CountryRequest $countryRequest,
    ): Response {
        // ...
    }
}
```


### Атрибут UnbindRequestParam

Атрибут `#[UnbindRequestParam]` решает обратную задачу — он **исключает свойство из связывания с данными запроса**.
Это полезно, когда значение свойства не приходит от пользователя, а формируется иначе:
вычисляется в конструкторе, является внутренним кэшем или служебным полем объекта.

Атрибут указывается на свойстве класса или promoted-параметре конструктора — именно там,
где описывается структура запроса (по аналогии с `#[BindRequestParam]`).

Отвязанное свойство:
- **исключается из контракта запроса** — оно не участвует в подготовке данных и не связывается с полями запроса;
- **исключается из валидации сырых данных** — его констрейнты не применяются на этапе проверки пользовательского ввода;
- **если поле всё же придёт в запросе — это будет расценено как лишнее поле** (см. ограничение ниже).

При этом значение свойства должно быть получено иным способом — вычислено в конструкторе,
задано значением по умолчанию и т.п. Финальная валидация собранного объекта при этом сохраняется:
`#[UnbindRequestParam]` управляет только связыванием с запросом, но не отключает проверку инвариантов DTO.

```php
use Cryonighter\ValidRequestBundle\Attribute\UnbindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class ArticleRequest
{
    // Отвязанное свойство: в запросе его быть не должно, значение вычисляется в конструкторе.
    #[UnbindRequestParam]
    #[Assert\NotBlank]
    public string $slug;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    ) {
        $this->slug = strtolower(str_replace(' ', '-', $this->title));
    }
}
```

#### Параметр "groups"

Как и `#[BindRequestParam]`, атрибут `#[UnbindRequestParam]` реагирует на группы валидации,
указанные через `#[ValidRequest]`. Это позволяет отвязывать свойство только в определённых контекстах,
а в остальных — связывать его как обычное поле запроса.

В примере ниже поле `reference` отвязано от запроса только для группы `Internal` (значение берётся из значения
по умолчанию). Для группы `Public` и всех остальных оно связывается как обычное поле:

```php
use Cryonighter\ValidRequestBundle\Attribute\UnbindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

class OrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[UnbindRequestParam(groups: ['Internal'])]
        #[Assert\NotBlank]
        public string $reference = 'computed-ref',
    ) {}
}

class OrderController
{
    // Группа Internal: reference отвязан, значение из значения по умолчанию.
    #[Route('/order/internal', methods: [Request::METHOD_POST])]
    public function internal(
        #[ValidRequest(groups: ['Default', 'Internal'])]
        OrderRequest $request,
    ): Response {
        // ...
    }

    // Группа Public: reference связывается как обычное поле запроса.
    #[Route('/order/public', methods: [Request::METHOD_POST])]
    public function public(
        #[ValidRequest(groups: ['Default', 'Public'])]
        OrderRequest $request,
    ): Response {
        // ...
    }
}
```

Атрибуты `#[BindRequestParam]` и `#[UnbindRequestParam]` можно комбинировать на одном свойстве,
если у них разные группы. Так свойство может связываться в одном контексте и быть отвязанным в другом:

```php
class ValueRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::query, groups: ['Read'])]
        #[UnbindRequestParam(groups: ['Create'])]
        #[Assert\NotBlank]
        public string $value = 'default',
    ) {}
}
```

В случае конфликта атрибутов `#[BindRequestParam]` и `#[UnbindRequestParam]` приоритет будет выдан последнему.


### Поддержка загрузки файлов

Бандл поддерживает загрузку файлов через `multipart/form-data`.
Используйте стандартный класс Symfony для загрузки файлов `UploadedFile` и источник данных `RequestValueSource::files`:

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class ProfileUserRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::files)]
        #[Assert\File(
            maxSize: '64k',
            mimeTypes: [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/webp',
                'image/svg+xml',
                'image/avif',
                'image/apng',
            ],
            maxSizeMessage: 'File is too large ({{ size }} {{ suffix }}). Maximum size is {{ limit }} {{ suffix }}',
            mimeTypesMessage: 'Please upload the correct image ({{ types }})'
        )]
        public ?UploadedFile $avatar,
    ) {}
}
```


### Преобразование типов

Для текстовых форматов передачи данных, таких как `application/x-www-form-urlencoded` или `multipart/form-data`
бандл автоматически выполняет нормализацию и приведение типов:

Для свойств класса типа `bool`:
значения '1', 'true', 'on', 'yes' будут интерпретированы как `true`
значения '0', 'false', 'off', 'no' будут интерпретированы как `false`
Все остальное — ошибка валидации.

Для свойств класса типа `int` из значения будут удалены все стандартные
разделители тысячных (пробелы, апострофы, кавычки, точки, запятые и т.д.).

Для свойств класса типа `float` будут применены те же изменения, что и для `int`,
а также определен разделитель целой и дробной части (точка или запятая).

Для форматов, поддерживающих эти типы, таких как `application/json`, эти преобразования не выполняются.
Если вы хотите выполнять их независимо от формата — можете переопределить дефолтные кастеры (см. ниже).

**Более сложные типы, такие как enum-объекты и встроенные объекты дат, не зависят от формата передачи данных и будут всегда приводиться к нужному типу.**

Для свойств класса типов `DateTime` и `DateTimeImmutable` преобразование будет зависеть от вида данных,
которые пришли на вход. Если они удовлетворяют условию `is_numeric`, то есть являются числом, то будут
интерпретированы как unixtime метка, в противном случае будет воспринят как строковая запись данных.
Сам интерфейс `DateTimeInterface` как тип свойства не поддерживается и вызовет ошибку.

Для свойств класса типа `BackedEnum` будет применена функция `BackedEnum::tryFrom()`.
Обычные (не backed) enum не поддерживаются.


### Псевдотипы PHP и другие специфичные типы

Бандл поддерживает псевдотипы PHP, такие `mixed`, `iterable` и `object`. Однако, поскольку
получить значения такого типа в PHP нельзя — они будут преобразованы в классические типы.

Тип `iterable` по умолчанию будет приведен к `array`, если не будет задан иной тип через атрибут `Assert\Type`.
Тип `object` по умолчанию будет приведен к `stdClass`, если не будет задан иной тип через атрибут `Assert\Type`.
Тип `mixed` по умолчанию ни к чему не будет приведен, если не будет задан тип через атрибут `Assert\Type`.

> Бандл не будет автоматически инстанцировать реализации интерфейса `Traversable`, заданного через `Assert\Type`,
> ввиду их большого разнообразия. Для получения инстанса такого интерфейса следует использовать патчеры (см. ниже).

Определение `callable` не возможно для свойств, это ограничение самого PHP.
То же касается типов `resource`, `void` и `never`.

Одиночные типы, такие как `true`, `false` и `null` читаются как примитивы и никак отдельно не обрабатываются.
Бандл не запрещает их использования, но не гарантирует какого-то определенного поведения при их обработке.


### Кастеры

Кастер — это сервис, который отвечает за нормализацию и приведение сырого значения из запроса к типу свойства класса.
Встроенные преобразования типов (`bool`, `int`, `float`, `DateTimeInterface`, `BackedEnum`), описанные выше,
реализованы именно через кастеры.

Механизм кастеров открыт для расширения: вы можете написать собственный кастер
для любого типа — например, ваших собственных Value Object, или переопределить встроенные.

Для создания кастера достаточно реализовать интерфейс `Cryonighter\ValidRequestBundle\Caster\ValueCasterInterface`,
сервис автоматически зарегистрируется в контейнере с нужными тегами и будет подхвачен бандлом.

Интерфейс состоит из двух методов:

- `supports(PropertyType $type): bool` — определяет, обрабатывает ли кастер данный тип свойства.
  Помимо имени типа, `PropertyType` предоставляет признаки `builtin`, `nullable` и `iterable`,
  что позволяет тонко управлять применимостью кастера.
- `cast(mixed $value, CastContext $context): CastResult` — выполняет преобразование значения.
  Верните `CastResult::converted($value)`, если преобразование выполнено, или `CastResult::skip()`,
  чтобы передать управление дальше (следующему кастеру или структурной логике сборки объекта).

`CastContext` несёт контекст текущего преобразования:
- тип свойства (`type`)
- источник данных (`source`)
- объект запроса (`request`)
- признак текстового протокола (`isTextProtocol`)

Последний удобен для кастеров, которые должны срабатывать только для строковых форматов передачи данных
(`application/x-www-form-urlencoded`, `multipart/form-data`), — встроенные `bool`/`int`/`float` работают именно так.

Например, реализуем кастер для Value Object UserId:

```php
use App\ValueObject\UserId;
use Cryonighter\ValidRequestBundle\Caster\CastContext;
use Cryonighter\ValidRequestBundle\Caster\CastResult;
use Cryonighter\ValidRequestBundle\Caster\ValueCasterInterface;
use Cryonighter\ValidRequestBundle\Schema\PropertyType;

class UserIdCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return !$type->builtin && is_a($type->name, UserId::class, true);
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        if (is_numeric($value) && $value > 0) {
            return CastResult::converted(new UserId($value));
        }

        return CastResult::skip();
    }
}
```

Кастеры перебираются по порядку, и побеждает первый, вернувший `CastResult::converted(...)`.
Встроенные кастеры зарегистрированы с пониженным приоритетом, поэтому ваш собственный кастер
для того же типа будет применён раньше встроенного без дополнительной настройки приоритетов.

> **Определение текстового протокола** осуществляется через сервис, реализующий интерфейс `TextProtocolDetector`.
> Если критерии «текстовости» протокола в вашем проекте отличаются от стандартных,
> вы можете переопределить его реализацию через стандартные механизмы Symfony.

Кастеры не будут применяться к полям, для которых определены патчеры (см. ниже).
Предполагается, что патчер сам возвращает приведённое к нужному виду значение.


### Патчеры

Патчер — это Symfony-сервис, позволяющий задать произвольную логику для установки значений свойств объекта.

Для создания патчера достаточно реализовать интерфейс `Cryonighter\ValidRequestBundle\Patcher\PatcherInterface`,
сервис автоматически зарегистрируется в контейнере с нужными тегами и будет подхвачен бандлом.

Патчер можно применить:
- **к отдельному свойству** — через параметр `patcher` атрибута `#[BindRequestParam]`.
- **ко всему объекту** — через параметр `patcher` атрибута `#[ValidRequest]`.

В обоих случаях параметр `patcher` принимает полное имя класса сервиса.

Патчер, применяемый **к отдельному свойству**, имеет максимальный приоритет при обработке свойств. С него
начинается подготовка данных и им же она заканчивается. К нему не применяются кастеры и иные преобразования.

Патчер, применяемый **ко всему объекту**, вызывается после подготовки данных и непосредственно перед их валидацией.
Он получит на вход данные со всеми примененными преобразованиями (включая патчеры свойств), а результат его работы
и будет теми данными, которыми будет гидрироваться объект.

Например, реализуем автоматическую генерацию `slug` из поля `title`, если пользователь не передал его явно:

```php
use Cryonighter\ValidRequestBundle\Contract\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugPatcher implements PatcherInterface
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function patch(mixed $value, Request $request): mixed
    {
        if ($value) {
            return $value;
        }

        $title = $request->request->get('title', '');

        return strtolower($this->slugger->slug($title));
    }
}
```

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class ArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,

        #[BindRequestParam(patcher: SlugPatcher::class)]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
    ) {}
}
```

**Осторожнее с патчерами!** Патчеры применяются всегда, безусловно.
Все условия их применения необходимо реализовать в самом патчере.

Например, если вы хотите получать null в контроллере при пустом запросе, но патчер
всегда возвращает какие-то данные — null в контроллере вы никогда не получите.


### Гидрация объектов

В отличие от многих других решений, где для гидрации объекта используется рефлексия для установки значений его свойств,
бандл `ValidRequestBundle` использует конструктор класса, сеттеры и публичные свойства (именно в таком порядке).
Установка свойств напрямую через рефлексию используется только как fallback для случаев, когда установить данные
иначе не получается: нет конструктора, свойства приватные, нет сеттеров, свойство помечено как readonly.

#### Конструктор

Важно отметить, что валидация происходит на основе свойств класса, а сборка объекта — через параметры конструктора.
И они не обязаны совпадать по именам и типам. К счастью, решить эту проблему нам поможет уже знакомый 
атрибут `#[BindRequestParam]`, который можно применить не только к свойствам класса, но и к параметрам конструктора.

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class UserRequest
{
    #[BindRequestParam(name: 'login')]
    private string $username;

    public function __construct(
        #[BindRequestParam(name: 'login')]
        string $email,
    ) {
        $this->username = $email;
    }
}
```

Если названия параметра запроса, параметра конструктора и свойства класса совпадают — они сопоставятся автоматически.

Если же название параметра в запросе отличается и от названия свойства, и от названия параметра
конструктора — укажите его явно в обоих местах через `#[BindRequestParam(name: ...)]`.

> Все параметры конструктора должны быть или присутствовать в запросе, должно быть установлено патчером,
> или быть nullable типа, иметь значение по умолчанию. Иначе конструктор просто не сможет быть вызван.
> Это базовое ограничение самого PHP, которое никак не связано с бандлом.

**Важно!** Поведение атрибута `#[BindRequestParam]` на обычных (не promoted) параметрах конструктора несколько
отличается от его поведения на свойствах. Изначально этот атрибут введен для свойств и promoted-параметров конструктора,
и именно там полностью раскрывает свой функционал: определяет название параметра в запросе, выбирает источник данных,
привязывает валидационные атрибуты и применяет патчер. Именно на свойствах, а не на параметрах конструктора, описывается
структура запроса.

На обычных параметрах конструктора атрибут `#[BindRequestParam]` не определяет названия параметра — к моменту его
обработки все параметры запроса уже определены и провалидированны. Он только привязывает параметр конструктора
к уже существующему параметру запроса.

Именно по этой причине атрибуту `#[BindRequestParam]` на параметре конструктора
нельзя задать источник данных — все данные из всех источников уже собраны. При попытке это сделать будет выброшено
исключение `ValidRequestException`, уведомляющее об этом.

Патчеры к простому параметру конструктора применить можно, но тут тоже есть нюанс: если патчер задан еще и для свойства
класса, с которым этот параметр сопоставляется, то патчер свойства применится первым, а патчер параметра конструктора
получит на вход результат, возвращенный первым патчером. Для promoted свойств этого не происходит - патчер применяется
один раз. Кроме того, патчер можно задать для параметра конструктора, который вообще не сопоставляется со свойствами класса.


#### Сеттеры

Еще один способ гидрации объекта — через публичные сеттеры. Если свойство класса отсутствует в параметрах конструктора,
но имеет сеттер, бандл вызовет его и передаст значение из запроса.

Сеттер должен быть публичным, принимать один аргумент и иметь конвенциональное название как `set{PropertyName}`.
А вот название аргумента значения не имеет.

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class UserRequest
{
    #[BindRequestParam(name: 'login')]
    private string $username;

    public function setUsername(string $email): void
    {
        $this->username = $email;
    }
}
```

#### Публичные свойства класса

Если свойство публично и не инициализируется через конструктор, бандл установит его напрямую.

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class UserRequest
{
    #[BindRequestParam(name: 'login')]
    public string $username;
}
```

#### Рефлексия

И все же, если ничего не подходит — бандл использует рефлексию и установит значение напрямую в свойство класса.

```php
use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;

class UserRequest
{
    #[BindRequestParam(name: 'login')]
    private string $username;
}
```


## Limitations

| Limitation                                                                              | Notes                                                                            |
|-----------------------------------------------------------------------------------------|----------------------------------------------------------------------------------|
| Поддерживаются только именованные типы свойств, а не указанный тип читается как `mixed` | Объединенные и пересекающиеся типы не поддерживаются                             |
| `Assert\Type` поддерживает только одиночный тип                                         | Передача массива типов в `Assert\Type` не поддерживается                         |
| Проверка на лишние поля в запросе работает только для `payload`-источника               | Для `query`, `headers`, `cookies` и других источников лишние поля не проверяются |
| Преобразование типов для `bool`, `int`, `float` работает только для протоколов `form`   | Для `application/xml` и `text/xml` поведение не определено и не протестировано   |


## Сравнение с нативными решениями Symfony

Начиная с Symfony 6.3, фреймворк предоставляет собственные атрибуты для маппинга данных запроса:
`#[MapRequestPayload]`, `#[MapQueryString]`, `#[MapQueryParameter]`, а с 8.1 — `#[MapUploadedFile]` и `#[MapRequestHeader]`.

Ключевое архитектурное отличие: нативные атрибуты используют **Symfony Serializer** для десериализации данных в объект,
и только после этого применяют валидатор к уже собранному объекту. `ValidRequestBundle` работает иначе —
он использует **рефлексию** и проводит предварительную валидацию сырых данных из запроса **до** сборки объекта,
что и устраняет ряд принципиальных ограничений нативного подхода.

### Потеря исходного значения при несоответствии типов

Основная проблема нативного подхода: если тип свойства в DTO не совпадает с тем, что передал пользователь,
Serializer либо выбрасывает исключение с HTTP 400, либо подставляет пустой объект — в обоих случаях
пользователь не получает внятной ошибки валидации с указанием конкретного поля.
Это известная проблема, зафиксированная в трекере Symfony [[1]](https://github.com/symfony/symfony/issues/50904) [[2]](https://github.com/symfony/symfony/issues/50759).

`ValidRequestBundle` валидирует исходные данные запроса до приведения типов,
поэтому пользователь всегда получает точную ошибку валидации именно для того значения, которое ввёл.

### Приведение сложных типов (enum, DateTime)

Обе системы умеют приводить `BackedEnum` и объекты дат, но подходы отличаются.

**Symfony.** Для отдельных аргументов есть `#[MapQueryParameter]` (поддерживает `\BackedEnum`) [[1]](https://symfony.com/doc/current/controller.html)
и `DateTimeValueResolver` вместе с атрибутом `#[MapDateTime]` для настройки формата [[2]](https://symfony.com/doc/current/controller/value_resolver.html).
Внутри DTO приведением занимается **Symfony Serializer** через `BackedEnumNormalizer` и `DateTimeNormalizer`;
формат даты для конкретного свойства задаётся атрибутом `#[Context]` [[3]](https://symfony.com/doc/current/serializer.html).
Это гибко и хорошо интегрировано, но требует Serializer и, как правило, конфигурации формата на уровне свойства.

**ValidRequestBundle.** Приведение выполняется кастерами (`ValueCasterInterface`) — без Serializer:

- `BackedEnum` приводится через `BackedEnum::tryFrom()`; обычные (не backed) enum не поддерживаются,
  так как не имеют скалярного представления и не могут быть переданы по HTTP.
- `DateTime` / `DateTimeImmutable` приводятся по эвристике: числовое значение (`is_numeric`)
  трактуется как Unix-время (с поддержкой дробной части как микросекунд), иначе значение
  разбирается как строковая дата. Интерфейс `DateTimeInterface` как тип свойства не поддерживается.

Ключевая разница в формате дат: Symfony ожидает явного указания формата через `#[Context]`,
тогда как бандл применяет единую эвристику «число — это timestamp, иначе строка».
Первый подход строже и предсказуемее для фиксированного API-контракта; второй — удобнее «из коробки»
для смешанных источников, но менее строг к формату входных данных.

Оба подхода расширяемы, но по-разному: в Symfony вы пишете кастомный нормализатор Serializer,
в бандле — кастер под конкретный тип (в том числе для собственных Value Object), при этом
приведение сложных типов не зависит от формата передачи данных (`application/json`,
`application/x-www-form-urlencoded` и др.).

### Разрешение конкретного класса вложенного объекта

Обе системы умеют выбирать конкретный класс для свойства, объявленного как интерфейс или
абстрактный класс, но делают это принципиально по-разному.

**Symfony.** Начиная с 4.1, Serializer поддерживает «discriminator class mapping» через атрибут
`#[DiscriminatorMap]` на интерфейсе или абстрактном классе [[1]](https://symfony.com/blog/new-in-symfony-4-1-serialize-and-deserialize-from-abstract-classes).
Конкретный класс определяется по значению специального поля-дискриминатора в самих данных
(например `"type": "product"`), а маппинг «значение → класс» задаётся на уровне базового типа [[2]](https://symfony.com/doc/current/serializer.html).
В Symfony 7.3 добавлен параметр `defaultType`, который подставляет класс по умолчанию,
если поле-дискриминатор в запросе отсутствует [[3]](https://symfony.com/blog/new-in-symfony-7-3-serializer-improvements).

```php
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(
    typeProperty: 'type',
    mapping: [
        'product' => Product::class,
        'shipping' => Shipping::class,
    ],
    defaultType: 'product',
)]
interface InvoiceItemInterface
{
    // ...
}
```

**ValidRequestBundle.** Конкретный класс задаётся через `#[Assert\Type]` прямо на свойстве DTO,
а не на базовом типе, и выбирается не по значению поля в данных, а по активной **группе валидации**.
Это позволяет один и тот же класс DTO использовать в разных контекстах (разные экшны с разными группами)
без служебного поля-дискриминатора в теле запроса.

```php
#[Assert\Type(type: Product::class, groups: ['Product'])]
#[Assert\Type(type: Shipping::class, groups: ['Shipping'])]
#[Assert\Valid]
public InvoiceItemInterface $invoice;
```

То же самое работает и для **массивов объектов**: у Symfony массив полиморфных элементов тоже
поддерживается — тип элемента типизируется через PHPDoc, а каждый элемент десериализуется по
своему полю-дискриминатору [[2]](https://symfony.com/doc/current/serializer.html).
Таким образом, разные элементы одного массива могут быть разных классов. В бандле же группа
едина для всего запроса, поэтому весь массив собирается в элементы одного выбранного класса.

```php
#[Assert\All([
    new Assert\Type(type: Product::class, groups: ['Product']),
    new Assert\Type(type: Shipping::class, groups: ['Shipping']),
])]
#[Assert\Valid]
public array $invoices;
```

Ключевые различия подходов:

| Аспект                              | Symfony `#[DiscriminatorMap]`             | ValidRequestBundle `#[Assert\Type]`        |
|-------------------------------------|-------------------------------------------|--------------------------------------------|
| Где объявляется маппинг             | На интерфейсе / абстрактном классе        | На свойстве DTO                            |
| Чем выбирается класс                | Значением поля-дискриминатора в данных    | Активной группой валидации                 |
| Служебное поле `type` в запросе     | Требуется (или `defaultType` в 7.3+)      | Не требуется                               |
| `object`, `mixed`, без типа         | ❌ (нужен интерфейс/абстрактный класс)    | ✅                                         |
| Выбор наследника у конкретного типа | ❌                                        | ✅                                         |
| Зависимость                         | `symfony/serializer`                      | только `symfony/validator`                 |

Подход Symfony удобен, когда клиент сам сообщает тип объекта в данных и структура полиморфна
«по природе». Подход бандла удобен, когда тип определяется контекстом обработки (группой),
а не содержимым запроса, и когда переопределять нужно не только интерфейсы и абстрактные классы,
но и `object` / `mixed` / нетипизированные свойства, а также выбирать конкретного наследника.

### Источники данных

| Источник данных          | Нативный Symfony     | ValidRequestBundle |
|--------------------------|----------------------|--------------------|
| Тело запроса (`payload`) | ✅                   | ✅                 |
| Query-параметры          | ✅                   | ✅                 |
| Route-атрибуты           | ❌                   | ✅                 |
| Cookies                  | ❌                   | ✅                 |
| Заголовки                | ✅ (с версии 8.1+)   | ✅                 |
| Серверные переменные     | ❌                   | ✅                 |
| Файлы                    | ✅ (с версии в 8.1+) | ✅                 |

Помимо этого, `ValidRequestBundle` позволяет смешивать источники **внутри одного DTO** через `#[BindRequestParam(source: ...)]` — например,
взять `id` из route-атрибутов, а остальные поля из тела запроса. В нативном подходе для этого потребуется несколько отдельных аргументов контроллера.

### Исключение свойства из связывания

Иногда свойство DTO не должно приходить от пользователя вовсе: оно вычисляется в конструкторе,
является внутренним кэшем или служебным полем. Обе системы позволяют исключить такое свойство,
но подходы различаются.

**Symfony.** Serializer поддерживает атрибут `#[Ignore]`, который исключает свойство как из сериализации,
так и из денормализации [[1]](https://symfony.com/doc/current/serializer.html) [[2]](https://symfony.com/blog/new-in-symfony-5-1-serializer-improvements).
Это статическое исключение: свойство, помеченное `#[Ignore]`, не участвует в маппинге всегда,
независимо от контекста обработки. Тонко управлять этим по контексту можно только через группы
сериализации (`#[Groups]`), которые задаются отдельно от групп валидации.

**ValidRequestBundle.** Атрибут `#[UnbindRequestParam]` исключает свойство именно из **связывания с запросом**:
оно не участвует в подготовке данных и не валидируется на этапе проверки сырого пользовательского ввода.
При этом исключение можно привязать к **группе валидации** — одно и то же свойство может быть отвязано
в одном контексте (экшн с одной группой) и связываться как обычное поле в другом.

```php
#[UnbindRequestParam(groups: ['Internal'])]
#[Assert\NotBlank]
public string $reference = 'computed-ref';
```

Обратите внимание на важное следствие философии бандла: раз отвязанное свойство исключается из контракта
запроса, то его случайная передача пользователем расценивается как **лишнее поле** — в отличие от Symfony,
где проигнорированное свойство просто молча отбрасывается. Это соответствует главной идее бандла —
строго валидировать именно пользовательский ввод.

### Обработка ошибок валидации

Нативные атрибуты при ошибке валидации автоматически выбрасывают `HttpException`
с HTTP кодом 422 [[3]](https://symfony.com/blog/new-in-symfony-6-3-mapping-request-data-to-typed-objects).
Это удобно для быстрого старта, но негибко: формат ответа фиксирован, и для его изменения
нужно перехватывать `HttpException`, а не доменное исключение валидации.

`ValidRequestBundle` выбрасывает `ValidationFailedException` из пакета `symfony/validator` —
стандартное доменное исключение без привязки к HTTP. Это даёт полный контроль над форматом ответа
через стандартные `Event Listener` / `Event Subscriber`. Дополнительно поддерживается передача ошибок
напрямую в контроллер через аргумент `ConstraintViolationListInterface` — без выброса исключения вовсе.

### Преобразование типов

Нативные атрибуты полагаются на Serializer, который ожидает, что клиент передаёт данные
уже в корректном формате (например, JSON с правильными типами).
Для текстовых форматов (`application/x-www-form-urlencoded`, `multipart/form-data`) это создаёт трудности:
все значения приходят строками, и приведение типов либо не работает, либо даёт неожиданный результат.

`ValidRequestBundle` выполняет нормализацию типов для текстовых протоколов явно:
`bool`, `int`, `float` корректно парсятся из строковых представлений, включая различные форматы записи чисел.

### Зависимости

Нативные атрибуты требуют наличия `symfony/serializer` и, как правило, `symfony/property-info` —
это ощутимые зависимости с широкими возможностями, которые могут быть избыточны, если Serializer
в проекте не используется нигде кроме маппинга запросов. Именно Serializer обеспечивает
приведение сложных типов (enum, DateTime) в нативном подходе.

`ValidRequestBundle` работает исключительно через PHP Reflection и `symfony/validator`
без каких-либо дополнительных зависимостей. Приведение как простых, так и сложных типов
реализовано собственными кастерами, поэтому Serializer не требуется.

### Расширяемость

Нативные атрибуты поддерживают кастомный `resolver` для переопределения логики
маппинга [[4]](https://symfony.com/blog/new-in-symfony-6-3-mapping-request-data-to-typed-objects),
а приведение отдельных типов настраивается через кастомные нормализаторы Serializer.
Это мощные механизмы, но они завязаны на инфраструктуру Serializer.

`ValidRequestBundle` предоставляет две лёгкие точки расширения без Serializer:
**патчеры** (`PatcherInterface`) — для точечного изменения значения свойства или всего объекта,
и **кастеры** (`ValueCasterInterface`) — для приведения значений к произвольным типам,
включая пользовательские Value Object.

### Гидрация объектов

Способ, которым данные «раскладываются» по объекту, у обеих систем принципиально разный.

**Symfony.** Гидрацией занимается Serializer через набор нормализаторов, и выбор стратегии,
по сути, означает выбор нормализатора:

- `ObjectNormalizer` (используется нативными атрибутами по умолчанию) — читает и пишет через
  PropertyAccess: напрямую в публичные свойства, а также через геттеры/сеттеры (`get`/`set`/`is`/`has`/`can`
  и adders/removers). Поддерживает вызов конструктора при денормализации [[1]](https://symfony.com/doc/current/serializer.html).
- `PropertyNormalizer` — читает и пишет свойства напрямую (в том числе `private`/`protected`)
  через рефлексию; тоже умеет вызывать конструктор [[1]](https://symfony.com/doc/current/serializer.html).
- `GetSetMethodNormalizer` — работает только через конструктор и `set`-методы [[1]](https://symfony.com/doc/current/serializer.html).

То есть в Symfony комбинация «конструктор + сеттеры + публичные свойства + рефлексия» не собрана
в единую стратегию: разные нормализаторы покрывают разные способы доступа, а `ObjectNormalizer`
опирается на PropertyAccess и напрямую в `private`-свойства без сеттеров не пишет.

**ValidRequestBundle.** Бандл применяет единую многоступенчатую стратегию гидрации в строгом порядке:
конструктор → публичные сеттеры (`set{PropertyName}`) → публичные свойства → и только как крайняя мера —
запись в свойство через рефлексию. Рефлексия при этом используется в первую очередь для сборки
метаданных, а не как основной механизм присваивания значений.

Дополнительно бандл разводит понятия «свойство для валидации» и «параметр конструктора для сборки»:
они не обязаны совпадать по именам и типам, а сопоставление настраивается атрибутом
`#[BindRequestParam]`, который допускается вешать как на свойство, так и на параметр конструктора.
В нативном подходе имя свойства при денормализации переопределяется через `#[SerializedName]`
и завязано на выбранный нормализатор.

### Итоговая таблица

| Возможность                                        | Нативный Symfony              | ValidRequestBundle         |
|----------------------------------------------------|-------------------------------|----------------------------|
| Валидация исходных данных до приведения типов      | ❌                            | ✅                         |
| Различение "поле отсутствует" и "поле пустое"      | ❌                            | ✅                         |
| Преобразование типов для текстовых протоколов      | ❌                            | ✅                         |
| Смешивание источников данных внутри одного DTO     | ❌                            | ✅                         |
| Передача ошибок в контроллер без исключения        | ❌                            | ✅                         |
| Атрибут на классе DTO (без указания в контроллере) | ❌                            | ✅                         |
| Кастомная логика для отдельных свойств             | ❌                            | ✅                         |
| Расширяемое приведение типов                       | ⚠️                            | ✅                         |
| Приведение `BackedEnum`                            | ✅                            | ✅                         |
| Приведение `DateTime` / `DateTimeImmutable`        | ✅                            | ✅                         |
| Настройка формата даты через атрибут               | ✅ (`#[Context]`)             | ❌ (эвристика)             |
| Выбор класса вложенного объекта                    | ✅ (по полю-дискриминатору)   | ✅ (по группе валидации)   |
| Исключение свойства из связывания                  | ✅ (`#[Ignore]`, статически)  | ✅ (по группе валидации)   |
| Работа без `symfony/serializer`                    | ❌                            | ✅                         |
| HTTP-статус из коробки при ошибке валидации        | ✅ (422)                      | ❌ (нужен listener)        |
| Единая стратегия гидрации                          | ⚠️ (зависит от нормализатора) | ✅                         |
| Раздельные имена свойства и параметра конструктора | ⚠️ (`#[SerializedName]`)      | ✅ (`#[BindRequestParam]`) |


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
# All tests
./vendor/bin/phpunit --display-warnings

# Only unit
./vendor/bin/phpunit --display-warnings --testsuite Unit
 
# Only integration
./vendor/bin/phpunit --display-warnings --testsuite Integration

# Specific file
./vendor/bin/phpunit --display-warnings tests/Unit/Service/SchemaSerializationTest.php

# With coating (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security-related issues, please email `cryonighter@yandex.ru` instead of using the issue tracker.

## Credits

- [Andrey Reshetchenko][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/

[ico-version]: https://img.shields.io/packagist/v/cryonighter/valid-request-bundle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/cryonighter/valid-request-bundle.svg?style=flat-square

[link-author]: https://github.com/cryonighter
[link-packagist]: https://packagist.org/packages/cryonighter/valid-request-bundle
[link-downloads]: https://packagist.org/packages/cryonighter/valid-request-bundle
