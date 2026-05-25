<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\ResolverMessage;
use App\Models\SlaRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Policies\MasterDataPolicy;
use App\Policies\ResolverMessagePolicy;
use App\Policies\TicketPolicy;
use App\Policies\TicketAttachmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Register explicit model policies used by controllers and Gate::authorize().
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketAttachment::class, TicketAttachmentPolicy::class);
        Gate::policy(ResolverMessage::class, ResolverMessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, MasterDataPolicy::class);
        Gate::policy(IssueType::class, MasterDataPolicy::class);
        Gate::policy(Team::class, MasterDataPolicy::class);
        Gate::policy(Priority::class, MasterDataPolicy::class);
        Gate::policy(SlaRule::class, MasterDataPolicy::class);
    }
}
