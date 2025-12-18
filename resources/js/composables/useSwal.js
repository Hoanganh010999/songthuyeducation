import Swal from 'sweetalert2';
import { useI18n } from './useI18n';

/**
 * SweetAlert2 Composable
 * Wrapper cho SweetAlert2 với i18n support và iOS/macOS style
 */
export function useSwal() {
    const { t } = useI18n();

    // iOS/macOS style configuration
    const iosStyle = {
        customClass: {
            popup: 'ios-popup',
            title: 'ios-title',
            htmlContainer: 'ios-text',
            confirmButton: 'ios-button ios-button-confirm',
            cancelButton: 'ios-button ios-button-cancel',
            actions: 'ios-actions',
        },
        buttonsStyling: false,
        backdrop: 'rgba(0, 0, 0, 0.4)',
        showClass: {
            popup: 'ios-show',
            backdrop: 'swal2-backdrop-show'
        },
        hideClass: {
            popup: 'ios-hide',
            backdrop: 'swal2-backdrop-hide'
        }
    };

    /**
     * Success alert
     */
    const success = (message, title = null) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'success',
            title: title || t('common.success'),
            text: message,
            confirmButtonText: t('common.ok'),
            timer: 3000,
            timerProgressBar: true,
            iconColor: '#34C759', // iOS green
        });
    };

    /**
     * Error alert
     */
    const error = (message, title = null) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'error',
            title: title || t('common.error'),
            text: message,
            confirmButtonText: t('common.ok'),
            iconColor: '#FF3B30', // iOS red
        });
    };

    /**
     * Warning alert
     */
    const warning = (message, title = null) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'warning',
            title: title || t('common.warning'),
            text: message,
            confirmButtonText: t('common.ok'),
            iconColor: '#FF9500', // iOS orange
        });
    };

    /**
     * Info alert
     */
    const info = (message, title = null) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'info',
            title: title || t('common.info'),
            text: message,
            confirmButtonText: t('common.ok'),
            iconColor: '#007AFF', // iOS blue
        });
    };

    /**
     * Confirm dialog
     */
    const confirm = (message, title = null, options = {}) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'question',
            title: title || t('common.confirm'),
            text: message,
            showCancelButton: true,
            confirmButtonText: options.confirmText || t('common.confirm'),
            cancelButtonText: options.cancelText || t('common.cancel'),
            iconColor: '#007AFF', // iOS blue
            reverseButtons: true,
            ...options,
        });
    };

    /**
     * Delete confirmation
     */
    const confirmDelete = (message = null, title = null) => {
        return Swal.fire({
            ...iosStyle,
            icon: 'warning',
            title: title || t('common.confirm_delete'),
            text: message || t('common.confirm_delete_message'),
            showCancelButton: true,
            confirmButtonText: t('common.delete'),
            cancelButtonText: t('common.cancel'),
            iconColor: '#FF3B30', // iOS red
            reverseButtons: true,
        });
    };

    /**
     * Toast notification (small popup)
     */
    const toast = (message, icon = 'success') => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        return Toast.fire({
            icon: icon,
            title: message
        });
    };

    /**
     * Loading alert
     */
    const loading = (message = null) => {
        return Swal.fire({
            title: message || t('common.loading'),
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    };

    /**
     * Close current alert
     */
    const close = () => {
        Swal.close();
    };

    /**
     * Input dialog
     */
    const input = (title, options = {}) => {
        return Swal.fire({
            title: title,
            input: options.inputType || 'text',
            inputLabel: options.inputLabel || '',
            inputPlaceholder: options.inputPlaceholder || '',
            inputValue: options.inputValue || '',
            showCancelButton: true,
            confirmButtonText: options.confirmText || t('common.ok'),
            cancelButtonText: options.cancelText || t('common.cancel'),
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            inputValidator: options.inputValidator || null,
            ...options,
        });
    };

    /**
     * Custom alert (full control)
     */
    const fire = (options) => {
        return Swal.fire(options);
    };

    // Convenient aliases for common use cases
    const showSuccess = (message, title = null) => success(message, title);
    const showError = (message, title = null) => error(message, title);
    const showWarning = (message, title = null) => warning(message, title);
    const showInfo = (message, title = null) => info(message, title);

    return {
        success,
        error,
        warning,
        info,
        confirm,
        confirmDelete,
        toast,
        loading,
        close,
        input,
        fire,
        // Convenient aliases
        showSuccess,
        showError,
        showWarning,
        showInfo,
        // Export Swal instance for advanced usage
        Swal,
    };
}

