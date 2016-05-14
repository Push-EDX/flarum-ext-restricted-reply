<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PushEDX\Tags\Access;

use Carbon\Carbon;
use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\Discussion;
use Flarum\Core\User;
use Flarum\Event\ScopeHiddenDiscussionVisibility;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class RestrictedReplyPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Discussion::class;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Dispatcher $events)
    {
        parent::subscribe($events);
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param Discussion $discussion
     * @return bool
     */
    public function before(User $actor, $ability, Discussion $discussion)
    {
        if ($ability == "reply") {
            // Wrap all discussion permission checks with some logic pertaining to
            // the discussion's tags. If the discussion has a tag that has been
            // restricted, and the user has this permission for that tag, then they
            // are allowed. If the discussion only has tags that have been
            // restricted, then the user *must* have permission for at least one of
            // them.
            $tags = $discussion->tags;

            if (count($tags)) {
                foreach ($tags as $tag) {
                    if ($tag->is_restricted) {
                        if (!$actor->hasPermission('tag'.$tag->id.'.discussion.'.$ability)) {
                            return false;
                        }
                    }
                }
            }
        }
    }
}
