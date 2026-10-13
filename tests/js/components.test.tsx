import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';

import { OrderTotals } from '@/components/store/order-totals';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Price } from '@/components/ui/price';
import { QuantityStepper } from '@/components/ui/quantity-stepper';

/** Match an element whose own text equals `text`, treating no-break spaces as spaces. */
const money = (text: string) => (_: string, el: Element | null) =>
    el !== null && el.children.length === 0 && (el.textContent ?? '').replaceAll(String.fromCharCode(160), ' ') === text;

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { store: { currency: 'MAD', timezone: 'Africa/Casablanca' }, flash: {}, errors: {} } }),
}));

describe('QuantityStepper', () => {
    function Harness({ max }: { max: number }) {
        const [value, setValue] = useState(1);
        return <QuantityStepper label="Quantity" value={value} max={max} onChange={setValue} />;
    }

    it('increments and decrements within bounds', async () => {
        const user = userEvent.setup();
        render(<Harness max={2} />);
        const group = screen.getByRole('group', { name: 'Quantity' });
        const increase = screen.getByRole('button', { name: 'Increase quantity' });
        const decrease = screen.getByRole('button', { name: 'Decrease quantity' });

        expect(decrease).toBeDisabled();
        await user.click(increase);
        expect(within(group).getByRole('status')).toHaveTextContent('2');
        expect(increase).toBeDisabled();
        await user.click(decrease);
        expect(within(group).getByRole('status')).toHaveTextContent('1');
    });
});

describe('Price', () => {
    it('shows a sale price with the regular price struck through and labelled for screen readers', () => {
        render(<Price amount={19900} regular={24900} />);
        expect(screen.getByText(money('199 MAD'))).toBeInTheDocument();
        expect(screen.getByText(money('249 MAD')).tagName).toBe('S');
        expect(screen.getByText('Sale price')).toHaveClass('sr-only');
    });

    it('shows a single price when not on sale', () => {
        const { container } = render(<Price amount={24900} regular={24900} from />);
        expect(container.querySelector('s')).toBeNull();
        expect(screen.getByText('From')).toBeInTheDocument();
    });
});

describe('OrderTotals', () => {
    it('lists subtotal, discount, delivery and the amount payable on delivery', () => {
        render(<OrderTotals currency="MAD" subtotal={50000} discount={5000} shipping={0} total={45000} couponCode="WELCOME10" />);
        expect(screen.getByText('Discount (WELCOME10)')).toBeInTheDocument();
        expect(screen.getByText(money('−50 MAD'))).toBeInTheDocument();
        expect(screen.getByText('Free')).toBeInTheDocument();
        expect(screen.getByText('To pay on delivery')).toBeInTheDocument();
        expect(screen.getByText(money('450 MAD'))).toBeInTheDocument();
    });

    it('asks for a delivery area when the total is not known yet', () => {
        render(<OrderTotals currency="MAD" subtotal={10000} discount={0} shipping={null} total={null} />);
        expect(screen.getByText('Choose a delivery area')).toBeInTheDocument();
    });
});

describe('Field', () => {
    it('connects the label, hint and error to the control', () => {
        render(
            <Field label="Phone number" name="phone" hint="The courier will call this number." error="Enter a valid phone number.">
                {(p) => <Input {...p} />}
            </Field>,
        );
        const input = screen.getByLabelText('Phone number');
        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAccessibleDescription('The courier will call this number. Enter a valid phone number.');
        expect(screen.getByRole('alert')).toHaveTextContent('Enter a valid phone number.');
    });
});
