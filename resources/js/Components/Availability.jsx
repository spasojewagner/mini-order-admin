export default function Availability({ value, stock }) {
    if (value === 'out') {
        return <span className="text-xs font-medium text-rose-600">Out of stock</span>;
    }
    if (value === 'low') {
        return <span className="text-xs font-medium text-amber-700">Only {stock} left</span>;
    }
    return <span className="text-xs font-medium text-emerald-700">In stock</span>;
}
