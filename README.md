# Valid Request Bundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Total Downloads][ico-downloads]][link-downloads]

Symfony bundle for building and validating objects from HTTP request data.

Стандартный механизм `ArgumentResolver` / `ValueResolver` для каждого DTO-класса
с Symfony Assert атрибутами и дальнейшей его валидации возникали ряд принципиальных ограничений:

1. Валидировался уже собранный объект, вследствие чего ситуации "поля вообще нет в запросе" и "поле имеет пустое значение" были неотличимы.
2. При использовании типизированных полей класса исходное значение, переданное пользователем, может потеряться и быть провалидировано неправильно.
Например, если в классе поле указано как int, а пользователь ввел строковое значение "two", то нужно будет привести его к 0, чтоб просто собрать объект.
В результате чего будет провалидировано значение 0, а пользователь не получит ошибки или получит ее не на то значение, которое вводил.
3. Ну и просто писать десятки и сотни резольверов на каждое DTO в проекте — это неудобно. Особенно если есть вложенные объекты.

Те же проблемы (кроме пункта 3) актуальны и для нативных атрибутов
маппинга данных Symfony (`#[MapRequestPayload]`, `#[MapQueryString]`).

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
    Cryonighter\ValidRequest\ValidRequestBundle::class => ['all' => true],
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
допускается указание этого атрибута непосредственно к аргументу контроллера.

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


### Nullable arguments

Если аргумент контроллера объявлен как nullable, то при отсутствии данных в запросе в него будет передан `null`.
Это позволяет одним и тем же экшнам обслуживать одновременно отображение и обработку формы:

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


### Ошибки валидации

По умолчанию при провале валидации бандл выбрасывает стандартное исключение `ValidationFailedException`
из пакета `symfony/validator` до передачи управления контроллеру.

Перехватить его можно через стандартные механизмы Symfony — `Event Listener` или `Event Subscriber`,
и вернуть пользователю удобный ответ с описанием ошибок.


### Передача ошибок валидации в контроллер

Если вы хотите обрабатывать ошибки валидации непосредственно в контроллере, добавьте следующим аргументом
экземпляр `ConstraintViolationListInterface` (из пакета `symfony/validator`).
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


### Вложенные объекты и массивы объектов

Вложенные объекты разрешаются автоматически по типу свойства.
Если тип объявлен как nullable — то при отсутствии данных в запросе свойству будет присвоено значение `null`.

Для массивов объектов используйте стандартные атрибуты `Assert\All` и `Assert\Type`,
указав в `Assert\Type` целевой класс элементов массива:

```php
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

class Settings
{
    // ...
}

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
        #[Assert\Valid]
        public ?Settings $settings,

        #[Assert\NotBlank]
        #[Assert\All(
            new Assert\NotBlank(),
            new Assert\Type(Contact::class),
        )]
        #[Assert\Valid]
        public array $contacts,
    ) {}
}
```


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


### Группы валидации

Группы валидации передаются через параметр `groups` атрибута `#[ValidRequest]`, аналогично передаче групп напрямую в сервис валидатора:

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

Для форматов, поддерживающих эти типы, таких как `application/json`, эти преобразования выполняться не будут.
Если вы хотите выполнять их независимо от формата — можете переопределить дефолтные кастеры (см. ниже).

**Более сложные типы, такие как enum-объекты и встроенные объекты дат, не зависят от формата передачи данных и будут всегда приводиться к нужному типу.**

Для свойств класса типов `DateTime` и `DateTimeImmutable` преобразование будет зависеть от вида данных,
которые пришли на вход. Если они удовлетворяют условию `is_numeric`, то есть являются числом, то будут
интерпретированы как unixtime метка, в противном случае будет воспринят как строковая запись данных.
Сам интерфейс `DateTimeInterface` как тип свойства не поддерживается и вызовет ошибку.

Для свойств класса типа `BackedEnum` будет применена функция `BackedEnum::tryFrom()`.
Обычные enum не поддерживаются.


### Кастеры

Кастер — это сервис, реализующий интерфейс `ValueCasterInterface`, который отвечает за
нормализацию и приведение сырого значения из запроса к типу свойства класса. Встроенные
преобразования типов (`bool`, `int`, `float`, `DateTimeInterface`, `BackedEnum`), описанные выше,
реализованы именно через кастеры.

Механизм кастеров открыт для расширения: вы можете написать собственный кастер
для любого типа — например ваших собственных Value Object.

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

Патчер — это Symfony-сервис, реализующий интерфейс `PatcherInterface`.
Он позволяет задать произвольную логику для установки или изменения значений свойств объекта.

Патчер можно применить:
- **к отдельному свойству** — через параметр `patcher` атрибута `#[BindRequestParam]`.
- **ко всему объекту** — через параметр `patcher` атрибута `#[ValidRequest]`.

В обоих случаях параметр `patcher` принимает полное имя класса сервиса.

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

**Осторожнее с патчерами!** Патечеры применяются всегда, безусловно.
Все условия их применения вы должны реализовать сами в вашем патчере.

Например, если вы хотите получать null в контроллере при пустом запросе,
но патчер всегда возвращает какие-то данные — null в контроллере вы никогда не получите.


### Гидрация объектов

В отличие от многих других решений, бандл не использует рефлексию для гидрации свойства объектов напрямую.
Вместо этого он использует конструктор класса, сеттеры и публичные свойства (именно в таком порядке),
а рефлексия применяется только для сборки метаданных объекта.

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

| Limitation                                                                            | Notes                                                                                                                  |
|---------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| Поддерживаются только типизированные свойства                                         | Свойства без явного типа (`mixed` или без type hint) обрабатываются как примитивы без какого-либо приведения типов     |
| Проверка на лишние поля в запросе работает только для `payload`-источника             | Для `query`, `headers`, `cookies` и других источников лишние поля не проверяются                                       |
| `Assert\Type` в `Assert\All` поддерживает только одиночный класс                      | Передача массива типов в `Assert\Type` внутри `Assert\All` не поддерживается                                           |
| Преобразование типов для `bool`, `int`, `float` работает только для протоколов `form` | Для `application/xml` и `text/xml` поведение не определено и не протестировано                                         |


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
| Работа без `symfony/serializer`                    | ❌                            | ✅                         |
| HTTP-статус из коробки при ошибке валидации        | ✅ (422)                      | ❌ (нужен listener)        |
| Поддержка `GroupSequence`                          | ✅                            | ✅                         |
| Единая стратегия гидрации                          | ⚠️ (зависит от нормализатора) | ✅                         |
| Раздельные имена свойства и параметра конструктора | ⚠️ (`#[SerializedName]`)      | ✅ (`#[BindRequestParam]`) |


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
# All tests
./vendor/bin/phpunit

# Only unit
./vendor/bin/phpunit --testsuite Unit

# Only integration
./vendor/bin/phpunit --testsuite Integration

# Specific file
./vendor/bin/phpunit tests/Unit/Service/ViolationTransformerTest.php

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
