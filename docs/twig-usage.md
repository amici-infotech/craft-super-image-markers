# Twig Usage

![Frontend Generated image map](images/visible-tooltip-at-frontend.png)

## Basic Output

Given a field handle named `entryMapperField`:

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if not mapper.image.isEmpty() and not mapper.markers.isEmpty() %}
    {% set image = mapper.image.one() %}

    <div class="image-map">
        <img class="image-map__image" src="{{ image.url }}" alt="{{ image.title }}">

        {% for item in mapper.markers.all() %}
            <button
                class="image-map__marker"
                type="button"
                style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%; background-color: {{ item.marker.color }};"
            >
                {{ loop.index }}
            </button>
        {% endfor %}
    </div>
{% endif %}
```

## Include Related Entry Links

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if not mapper.image.isEmpty() and not mapper.markers.isEmpty() %}
    {% set image = mapper.image.one() %}

    <div class="image-map">
        <img class="image-map__image" src="{{ image.url }}" alt="{{ image.title }}">

        {% for item in mapper.markers.all() %}
            {% set relatedEntry = item.marker.entry.one() %}

            {% if relatedEntry %}
                <a
                    class="image-map__marker"
                    href="{{ relatedEntry.url }}"
                    style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%; background-color: {{ item.marker.color }};"
                >
                    {{ relatedEntry.title }}
                </a>
            {% else %}
                <span
                    class="image-map__marker"
                    style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%; background-color: {{ item.marker.color }};"
                ></span>
            {% endif %}
        {% endfor %}
    </div>
{% endif %}
```

## Suggested CSS

```css
.image-map {
  position: relative;
  display: inline-block;
  max-width: 100%;
}

.image-map__image {
  display: block;
  width: 100%;
  height: auto;
}

.image-map__marker {
  position: absolute;
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid #fff;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgb(0 0 0 / 30%);
  transform: translate(-50%, -50%);
}
```

## Accessors

The field value exposes these properties:

```twig
{# Image reference #}
entry.entryMapperField.image.one()
entry.entryMapperField.image.all()
entry.entryMapperField.image.isEmpty()

{# Marker collection #}
entry.entryMapperField.markers.all()
entry.entryMapperField.markers.one()
entry.entryMapperField.markers.count()
entry.entryMapperField.markers.isEmpty()

{# Marker item #}
item.marker.x
item.marker.y
item.marker.color
item.marker.entryId
item.marker.entry.one()
item.marker.entry.all()
item.marker.entry.isEmpty()

{# Eagerly prepare image and marker entries #}
{% set mapper = entry.entryMapperField.process() %}
```

## Empty State

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if mapper.image.isEmpty() %}
    <p>No image map has been configured yet.</p>
{% elseif mapper.markers.isEmpty() %}
    {% set image = mapper.image.one() %}
    <img src="{{ image.url }}" alt="{{ image.title }}">
{% else %}
    {# Render full image map #}
{% endif %}
```

## Working With Marker Data

Marker coordinates are numbers from `0` to `100`.

```twig
{% for item in entry.entryMapperField.markers.all() %}
    Marker {{ loop.index }}:
    X {{ item.marker.x }}%,
    Y {{ item.marker.y }}%,
    Color {{ item.marker.color }},
    Entry ID {{ item.marker.entryId ?? 'none' }}
{% endfor %}
```

Use `left` and `top` CSS percentages with `transform: translate(-50%, -50%)` to center the marker on its coordinate.

## Global Set Example

If the field is attached to a global set:

```twig
{% set imageMarker = general.imageMarker.process() %}

{% if imageMarker and not imageMarker.image.isEmpty() and not imageMarker.markers.isEmpty() %}
    {% set image = imageMarker.image.one() %}
    {% set markers = imageMarker.markers.all() %}
{% endif %}
```

## Example Templates

The plugin includes three Tailwind CDN examples in `example-templates/`:

- `tooltip.twig` - tooltip markers.
- `modal.twig` - modal marker details.
- `info-window.twig` - map-style info windows.

Copy them into your Craft project's `templates/` folder and adjust the routes and field source for your project.
