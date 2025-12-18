import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

export function useAccountingSwal() {
  const { t } = useI18n();

  const showSuccess = async (message) => {
    return await Swal.fire({
      icon: 'success',
      title: t('accounting.success'),
      text: message,
      timer: 2000,
      showConfirmButton: false
    });
  };

  const showError = async (message) => {
    return await Swal.fire({
      icon: 'error',
      title: t('accounting.error'),
      text: message
    });
  };

  const showConfirm = async (title, text) => {
    return await Swal.fire({
      icon: 'warning',
      title: title,
      text: text,
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: t('accounting.confirm'),
      cancelButtonText: t('accounting.cancel')
    });
  };

  const showDeleteConfirm = async (itemName) => {
    return await Swal.fire({
      icon: 'warning',
      title: t('accounting.delete_warning'),
      text: `${t('accounting.delete_confirm_text')} "${itemName}"?`,
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: t('accounting.delete'),
      cancelButtonText: t('accounting.cancel')
    });
  };

  return {
    showSuccess,
    showError,
    showConfirm,
    showDeleteConfirm
  };
}

