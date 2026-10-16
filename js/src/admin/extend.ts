import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import SettingsPage from './components/SettingsPage';

export default [
  new Extend.Admin()
    .page(SettingsPage)
    .permission(
      () => ({
        permission: 'recycleBin.deleteOwnForever',
        icon: 'fas fa-trash-alt',
        label: app.translator.trans('walsgit-recycle-bin.admin.permissions.deleteOwnForever'),
      }),
      'moderate'
    ),
];
