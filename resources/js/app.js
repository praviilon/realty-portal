import { initFlowbite } from 'flowbite';
import './yandex-map';

// Livewire с wire:navigate подменяет DOM без полной перезагрузки страницы —
// переинициализируем Flowbite-компоненты (аккордеон, дропдауны и т.д.) после
// каждой такой навигации, иначе они перестанут реагировать на клики.
document.addEventListener('livewire:navigated', () => initFlowbite());
initFlowbite();
