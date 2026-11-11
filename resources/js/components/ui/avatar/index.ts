import { cva, type VariantProps } from 'class-variance-authority';

export { default as Avatar } from './Avatar.vue';
export { default as AvatarFallback } from './AvatarFallback.vue';
export { default as AvatarImage } from './AvatarImage.vue';

export const avatarVariant = cva(
    'inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full bg-secondary font-medium text-secondary-foreground',
    {
        variants: {
            size: {
                xs: 'h-6 w-6 text-[10px]',
                sm: 'h-8 w-8 text-xs',
                md: 'h-10 w-10 text-sm',
                base: 'h-16 w-16 text-2xl',
                lg: 'h-32 w-32 text-5xl',
            },
            shape: {
                circle: 'rounded-full',
                square: 'rounded-lg',
            },
        },
        defaultVariants: {
            size: 'sm',
            shape: 'circle',
        },
    },
);

export type AvatarVariants = VariantProps<typeof avatarVariant>;
