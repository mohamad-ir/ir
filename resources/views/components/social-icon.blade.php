@props(['network'])
@switch($network)
    @case('linkedin')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
        @break
    @case('x')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.21-6.82-5.97 6.82H1.67l7.73-8.84L1.25 2.25h6.83l4.71 6.23 5.45-6.23zm-1.16 17.52h1.83L7.08 4.13H5.12l11.96 15.64z"/></svg>
        @break
    @case('youtube')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>
        @break
    @case('instagram')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
        @break
    @case('facebook')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07z"/></svg>
        @break
    @case('researchgate')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.6 0H4.4A4.4 4.4 0 0 0 0 4.4v15.2A4.4 4.4 0 0 0 4.4 24h15.2a4.4 4.4 0 0 0 4.4-4.4V4.4A4.4 4.4 0 0 0 19.6 0zM9.7 17.9c-.4.5-1.6.6-2.3.3-1.1-.4-1.6-1.6-2.2-2.6-.4-.7-.9-1.3-1.5-1.8v4.6c0 .5.4.7.9.7h.3v.4H.8v-.4h.3c.5 0 .8-.3.8-.7V8.2c0-.5-.3-.7-.8-.7H.8v-.4h4.2c1.8 0 3.6.5 3.6 2.6 0 1.4-.9 2.4-2.2 2.7.9.7 1.4 1.9 2.1 2.9.6.8 1.1 1.6 1.9 1.7v.4zm11-6.4h-2.4v2.3h-.5c0-1.3-.6-2.5-1.9-2.5-2 0-2.4 2.3-2.4 3.9 0 1.6.6 3.6 2.4 3.6 1.2 0 2-.9 2-2.1v-.5h-2.2v-.5h5v.5h-1v4.5h-.4l-.6-1c-.8.8-1.8 1.2-2.9 1.2-2.6 0-4.4-2.3-4.4-4.8s1.9-4.9 4.6-4.9c1 0 2 .4 2.7 1.1l.5-.9h.4v.5zM4.6 7.6H3.4v4.4h1c1.6 0 2.4-.6 2.4-2.3s-1-2.1-2.2-2.1z"/></svg>
        @break
    @case('orcid')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0a12 12 0 1 0 0 24 12 12 0 0 0 0-24zM7.4 17.6H6V7.8h1.4v9.8zM6.7 6.8a.9.9 0 1 1 0-1.8.9.9 0 0 1 0 1.8zm4 10.8V7.8h3.8c3.6 0 5.2 2.6 5.2 4.9 0 2.5-2 4.9-5.2 4.9h-3.8zm1.4-1.3h2.3c3.2 0 4-2.4 4-3.6 0-2-1.3-3.6-4-3.6h-2.3v7.2z"/></svg>
        @break
    @case('google_scholar')
        <svg {{ $attributes }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 24a7 7 0 1 1 0-14 7 7 0 0 1 0 14zm0-24L0 9.5l4.84 3.94A8 8 0 0 1 12 9a8 8 0 0 1 7.16 4.44L24 9.5z"/></svg>
        @break
    @default
        <x-heroicon-o-link {{ $attributes }} />
@endswitch
