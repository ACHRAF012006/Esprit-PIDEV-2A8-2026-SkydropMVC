<?php
/**
 * S1 - TailAdmin class strings shared by the User back-office views (copied from the template's form-elements and
 * buttons pages, checked by tests/check_tailadmin_classes.php). Included by each back-office view of the module.
 */
$errors = $errors ?? [];
$ui = [
    'label' => 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400',
    'input' => 'h-11 w-full min-w-0 rounded-[10px] border border-gray-300 bg-white px-4 py-2.5 text-sm font-normal text-gray-800 shadow-theme-xs outline-hidden transition-colors placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-500 dark:focus:border-brand-500/40',
    'input_error' => 'h-11 w-full min-w-0 rounded-[10px] border border-red-500 bg-white px-4 py-2.5 text-sm font-normal text-gray-800 shadow-theme-xs ring-3 ring-red-500/20 outline-hidden transition-colors placeholder:text-gray-400 focus:border-red-500 focus:ring-3 focus:ring-red-500/20 dark:border-red-500/50 dark:bg-gray-900 dark:text-white/90 dark:ring-red-500/40 dark:placeholder:text-gray-500',
    'error' => 'mt-1 text-xs text-red-500 dark:text-red-400',
    'help' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
    'primary' => 'inline-flex h-11 items-center justify-center gap-2 rounded-[10px] border border-transparent bg-brand-500 px-5 py-3 text-sm font-medium text-white shadow-theme-xs transition-all outline-none select-none hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-hidden active:translate-y-px dark:bg-brand-500 dark:hover:bg-brand-600',
    'outline' => 'inline-flex h-10 items-center justify-center gap-2 rounded-[10px] border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-all outline-none select-none hover:bg-gray-50 hover:text-gray-800 focus-visible:ring-3 focus-visible:ring-gray-300/50 focus-visible:outline-hidden active:translate-y-px dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50 dark:hover:text-white',
    'card' => 'overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/5 dark:bg-white/3',
    'th' => 'px-5 py-3 text-start text-xs font-medium text-gray-500 sm:px-6 dark:text-gray-400',
    'td' => 'px-5 py-4 text-start text-sm text-gray-500 sm:px-6 dark:text-gray-400',
    'strong' => 'block text-sm font-medium text-gray-800 dark:text-white/90',
    'small' => 'block text-xs text-gray-500 dark:text-gray-400',
    'badge' => 'inline-flex h-6 w-fit shrink-0 items-center justify-center gap-1 rounded-lg px-2.5 py-0.5 text-sm font-medium whitespace-nowrap',
    'status' => [
        'active' => 'bg-green-50 text-green-600',
        'deactivated' => 'bg-gray-100 text-gray-800 dark:bg-white/5 dark:text-white/80',
    ],
];
/** The input classes of a field, red when it has an error. */
$field = static fn (string $name): string => isset($errors[$name]) ? $ui['input_error'] : $ui['input'];
/** The message under a field: its error in red, otherwise an optional help text. */
$error = static function (string $name, string $help = '') use ($errors, $ui): string {
    if (isset($errors[$name])) {
        return '<p class="' . $ui['error'] . '" id="' . e($name) . '-error">' . e($errors[$name]) . '</p>';
    }
    return $help !== '' ? '<p class="' . $ui['help'] . '">' . e($help) . '</p>' : '';
};
?>
