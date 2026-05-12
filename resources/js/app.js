import Alpine from 'alpinejs';
import { createEditorApp } from './editor.js';

window.Alpine = Alpine;

Alpine.data('editorApp', createEditorApp);

Alpine.start();
