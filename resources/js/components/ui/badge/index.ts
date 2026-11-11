import { cva, type VariantProps } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva('inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium', {
    variants: {
        variant: {
            neutral: 'border-border bg-muted text-secondary-foreground',
            outline: 'border-border bg-transparent text-muted-foreground',
            success: 'border-transparent bg-success-soft text-success-strong',
            warning: 'border-transparent bg-warning-soft text-warning-strong',
            danger: 'border-transparent bg-destructive-soft text-destructive-strong',
            info: 'border-transparent bg-info-soft text-info-strong',
            ai: 'border-ai-border bg-ai-soft text-ai-foreground',
            'tag-person': 'border-tag-person-border bg-tag-person-soft text-tag-person',
            'tag-division': 'border-tag-division-border bg-tag-division-soft text-tag-division',
            'tag-topic': 'border-tag-topic-border bg-tag-topic-soft text-tag-topic',
        },
    },
    defaultVariants: {
        variant: 'neutral',
    },
});

export type BadgeVariants = VariantProps<typeof badgeVariants>;
