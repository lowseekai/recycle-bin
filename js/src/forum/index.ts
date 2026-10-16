import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostControls from 'flarum/forum/utils/PostControls';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';

let canDeleteOwnForever = false;

function confirmPermanentDelete(kind: 'post' | 'discussion', id: string) {
  const message = app.translator.trans('walsgit-recycle-bin.forum.permanent_delete.' + kind + '_confirmation');

  if (!window.confirm(String(message))) return;

  app
    .request({
      method: 'DELETE',
      url: app.forum.attribute('apiUrl') + '/recycle-bin/' + kind + 's/' + id + '/permanent',
    })
    .then(() => window.location.reload())
    .catch(() => {
      app.alerts.show({ type: 'error' }, app.translator.trans('walsgit-recycle-bin.forum.permanent_delete.error'));
    });
}

app.initializers.add('walsgit/recycle-bin', () => {
  app
    .request<{ canDeleteOwnForever: boolean }>({
      method: 'GET',
      url: '/api/recycle-bin/permissions',
    })
    .then((result) => {
      canDeleteOwnForever = result.canDeleteOwnForever;
      m.redraw();
    });

  extend(PostControls, 'userControls', (items, post) => {
    const user = app.session.user;
    const author = post.user();

    if (!canDeleteOwnForever || !user || !author || author.id() !== user.id()) return;

    items.add('recycle-bin-permanent-delete', Button.component({
      icon: 'fas fa-trash-alt',
      onclick: () => confirmPermanentDelete('post', post.id() as string),
    }, app.translator.trans('walsgit-recycle-bin.forum.permanent_delete.button')));
  });

  extend(DiscussionControls, 'userControls', (items, discussion) => {
    const user = app.session.user;
    const author = discussion.user();
    const participantCount = Number(discussion.attribute('participantCount') || 0);

    if (!canDeleteOwnForever || !user || !author || author.id() !== user.id() || participantCount > 1) return;

    items.add('recycle-bin-permanent-delete', Button.component({
      icon: 'fas fa-trash-alt',
      onclick: () => confirmPermanentDelete('discussion', discussion.id() as string),
    }, app.translator.trans('walsgit-recycle-bin.forum.permanent_delete.button')));
  });
});
