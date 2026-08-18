<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\PasswordPolicy;
use App\Rules\RussianName;
use App\Rules\RussianPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Регресс на баг из отчёта пользователя (после Вехи 3, п.1):
 *
 * 1) Маска телефона (Alpine `x-mask`) была записана как JS-строка в кавычках:
 *    x-mask="'+7 (999) 999-99-99'". Плагин @alpinejs/mask (он встроен в
 *    бандл Livewire) без модификатора `:dynamic` НЕ вычисляет expression как
 *    JS — он использует его как есть, посимвольно, как шаблон маски. Значит
 *    обрамляющие одинарные кавычки становились ЧАСТЬЮ шаблона: ведущая
 *    кавычка всегда попадала в результат как обычный литеральный символ
 *    маски (перед номером), а замыкающая — почти никогда не выводилась,
 *    так как форматирование останавливается, как только заканчиваются
 *    введённые цифры (то есть до того, как алгоритм доходит до конца
 *    шаблона). Отсюда — апостроф перед номером телефона и НИКОГДА после,
 *    и итоговое значение вида "'+7 (902) 111-22-33" не проходило серверную
 *    валидацию RussianPhone (регулярное выражение начинается с ^\+7, а не
 *    с апострофа) — то есть "любой телефон не проходит проверку".
 *    Исправление: x-mask="+7 (999) 999-99-99" без обрамляющих кавычек.
 *
 * 2) Сообщения кастомных правил валидации (RussianName/RussianPhone/
 *    PasswordPolicy) брались через trans('validation.xxx') без явной
 *    локали — если на сервере APP_LOCALE/APP_FALLBACK_LOCALE не был
 *    выставлен в 'ru', Laravel не находил перевод и возвращал сам ключ
 *    строкой — пользователь видел "validation.russian_phone" вместо
 *    текста ошибки. Исправление: trans('validation.xxx', [], 'ru') —
 *    сайт русскоязычный, сообщение всегда должно быть на русском
 *    независимо от локали сервера.
 */
class BugfixPhoneMaskAndValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_mask_attribute_has_no_wrapping_js_quotes(): void
    {
        $content = $this->get('/register')->getContent();

        $this->assertStringContainsString('x-mask="+7 (999) 999-99-99"', $content);
        $this->assertStringNotContainsString("x-mask=\"'+7", $content);
    }

    public function test_profile_form_mask_attribute_has_no_wrapping_js_quotes(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)->get('/profile')->getContent();

        $this->assertStringContainsString('x-mask="+7 (999) 999-99-99"', $content);
        $this->assertStringNotContainsString("x-mask=\"'+7", $content);
    }

    public function test_russian_phone_rule_rejects_value_with_stray_leading_apostrophe(): void
    {
        // Именно такое значение раньше уходило на сервер из-за сломанной маски.
        $validator = Validator::make(
            ['phone' => "'+7 (902) 111-22-33"],
            ['phone' => [new RussianPhone()]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_russian_phone_rule_accepts_correctly_formatted_value(): void
    {
        $validator = Validator::make(
            ['phone' => '+7 (902) 111-22-33'],
            ['phone' => [new RussianPhone()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_validation_messages_are_russian_regardless_of_app_locale(): void
    {
        $originalLocale = App::getLocale();

        try {
            // Симулируем ситуацию, из-за которой на проде вылезали сырые ключи:
            // локаль запроса выставлена не в 'ru' (например, сервер не
            // переопределил APP_LOCALE/APP_FALLBACK_LOCALE из .env.example).
            App::setLocale('en');

            $phoneValidator = Validator::make(['phone' => 'not-a-phone'], ['phone' => [new RussianPhone()]]);
            $phoneValidator->fails();
            $this->assertSame(
                'Поле телефон должно быть в формате +7 (XXX) XXX-XX-XX.',
                $phoneValidator->errors()->first('phone')
            );

            $nameValidator = Validator::make(['name' => 'John'], ['name' => [new RussianName()]]);
            $nameValidator->fails();
            $this->assertStringContainsString('должно содержать от 2 до 40 символов', $nameValidator->errors()->first('name'));
            $this->assertStringNotContainsString('validation.russian_name', $nameValidator->errors()->first('name'));

            $passwordValidator = Validator::make(['password' => 'пароль123'], ['password' => [new PasswordPolicy()]]);
            $passwordValidator->fails();
            $this->assertStringContainsString('латиницей', $passwordValidator->errors()->first('password'));
            $this->assertStringNotContainsString('validation.password_policy', $passwordValidator->errors()->first('password'));
        } finally {
            App::setLocale($originalLocale);
        }
    }

    public function test_registration_error_messages_never_show_raw_translation_keys(): void
    {
        $originalLocale = App::getLocale();

        try {
            App::setLocale('en');

            Livewire::test('pages.auth.register')
                ->set('first_name', 'John')
                ->set('email', 'valid@example.com')
                ->set('phone', "'+7 (902) 111-22-33")
                ->set('password', 'password')
                ->set('password_confirmation', 'password')
                ->call('register')
                ->assertHasErrors(['first_name', 'phone', 'password']);
        } finally {
            App::setLocale($originalLocale);
        }
    }

    public function test_user_can_change_phone_end_to_end_via_livewire_component(): void
    {
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('phone', '+7 (915) 123-45-67')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('+7 (915) 123-45-67', $user->refresh()->phone);
    }
}
